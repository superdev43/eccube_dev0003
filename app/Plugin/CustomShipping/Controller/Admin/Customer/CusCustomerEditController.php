<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\CustomShipping\Controller\Admin\Customer;

use DateInterval;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Admin\CustomerType;
use Eccube\Repository\CustomerRepository;
use Eccube\Util\StringUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;



class CusCustomerEditController extends AbstractController
{
    /**
     * コンテナ
     */
    protected $container;

    /**
     * 汎用処理用ユーティリティ
     */
    protected $util;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * コンストラクタ
     * @param ContainerInterface $container
     * @return void
     */
    public function __construct(
        ContainerInterface $container,
        CustomerRepository $customerRepository,
        EncoderFactoryInterface $encoderFactory
    ) {
        $this->customerRepository = $customerRepository;
        $this->encoderFactory = $encoderFactory;
        $this->container = $container;
        $this->util = $container->get('vt4g_plugin.service.util');
    }

    /**
     * @Route("/%eccube_admin_route%/customer/new", name="admin_customer_new")
     * @Route("/%eccube_admin_route%/customer/{id}/edit", requirements={"id" = "\d+"}, name="admin_customer_edit")
     * @Template("@CustomShipping/admin/Customer/edit.twig")
     */
    public function cus_index_free(Request $request, $id = null)
    {
        $pluginSetting = $this->util->getPluginSetting();
        $this->entityManager->getFilters()->enable('incomplete_order_status_hidden');
        // 編集
        if ($id) {
            $Customer = $this->customerRepository
                ->find($id);

            if (is_null($Customer)) {
                throw new NotFoundHttpException();
            }

            $oldStatusId = $Customer->getStatus()->getId();
            // 編集用にデフォルトパスワードをセット
            $previous_password = $Customer->getPassword();
            $Customer->setPassword($this->eccubeConfig['eccube_default_password']);
            // 新規登録

            $currentDate = new \DateTime();
            $beforemonth= $currentDate->sub(new DateInterval('P1M'));
            $lastDate = $Customer->getLastTimeRecurringDateTime();
            if ($lastDate->format('Y-m-d H:i:s') === $beforemonth->format('Y-m-d H:i:s')) {
                $Customer->setLastTimeRecurringDateTime($currentDate);
                $em = $this->getDoctrine()->getManager();
                $em->persist($Customer);
                $em->flush();
            }
        } else {
            $Customer = $this->customerRepository->newCustomer();

            $oldStatusId = null;
        }



        // 会員登録フォーム
        $builder = $this->formFactory
            ->createBuilder(CustomerType::class, $Customer);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_EDIT_INDEX_INITIALIZE, $event);

        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            log_info('会員登録開始', [$Customer->getId()]);

            $encoder = $this->encoderFactory->getEncoder($Customer);

            if ($Customer->getPassword() === $this->eccubeConfig['eccube_default_password']) {
                $Customer->setPassword($previous_password);
            } else {
                if ($Customer->getSalt() === null) {
                    $Customer->setSalt($encoder->createSalt());
                    $Customer->setSecretKey($this->customerRepository->getUniqueSecretKey());
                }
                $Customer->setPassword($encoder->encodePassword($Customer->getPassword(), $Customer->getSalt()));
            }

            // 退会ステータスに更新の場合、ダミーのアドレスに更新
            $newStatusId = $Customer->getStatus()->getId();
            if ($oldStatusId != $newStatusId && $newStatusId == CustomerStatus::WITHDRAWING) {
                $Customer->setEmail(StringUtil::random(60) . '@dummy.dummy');
            }
            $Customer->setCusCustomerLevel(2);
            $this->entityManager->persist($Customer);
            $this->entityManager->flush();

            log_info('会員登録完了', [$Customer->getId()]);

            $event = new EventArgs(
                [
                    'form' => $form,
                    'Customer' => $Customer,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_EDIT_INDEX_COMPLETE, $event);

            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('admin_customer_edit', [
                'id' => $Customer->getId(),
            ]);
        }

        return [
            'form' => $form->createView(),
            'Customer' => $Customer,
            'tokenApiKey' => $pluginSetting['token_api_key']
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/customer/new/prem", name="admin_customer_new_prem")
     * @Route("/%eccube_admin_route%/customer/{id}/edit/prem", requirements={"id" = "\d+"}, name="admin_customer_edit_prem")
     * @Template("@CustomShipping/admin/Customer/edit.twig")
     */
    public function cus_index_prem(Request $request, $id = null)
    {
        $pluginSetting = $this->util->getPluginSetting();

        define('ERROR_PAGE_TITLE', 'System Error');
        define('NORMAL_PAGE_TITLE', 'VeriTrans 4G - 会員カード管理サンプル画面');
        define('TXN_FAILURE_CODE', 'failure');
        define('TXN_PENDING_CODE', 'pending');
        define('TXN_SUCCESS_CODE', 'success');
        $order_id = "dummy" . time();
        $is_with_capture = "true";
        $jpo = "10";
        $payment_amount = $pluginSetting['recurring_amount'];
        // require_once(MDK_DIR . "3GPSMDK.php");
        $card_info = $request->get("admin_customer_cus");
        $request_data = new \RecurringAddRequestDto();

        $request_data->setToken($card_info['credit_token']);
        $request_data->setAccountId(1);
        $request_data->setGroupId($pluginSetting['recurring_group_id']);
        $start_date_type = new \DateTime();
        $start_date = $start_date_type->format('Ymd');
        $request_data->setStartDate($start_date);
        // $request_data->setEndDate("20250809");
        $request_data->setOneTimeAmount($payment_amount);
        $request_data->setAmount($payment_amount);

        /**
         * 実施
         */
        $transaction = new \TGMDK_Transaction();
        $response_data = $transaction->execute($request_data);

        //予期しない例外
        if (!isset($response_data)) {
            $page_title = ERROR_PAGE_TITLE;
        } else {
            $page_title = NORMAL_PAGE_TITLE;

            /**
             * 取引ID取得
             */
            $result_order_id = $response_data->getOrderId();
            /**
             * 結果コード取得
             */
            $txn_status = $response_data->getMStatus();
            /**
             * 詳細コード取得
             */
            $txn_result_code = $response_data->getVResultCode();
            /**
             * エラーメッセージ取得
             */
            $error_message = $response_data->getMerrMsg();

            /**
             * PayNowIDレスポンス取得
             */
            $pay_now_id_res = $response_data->getPayNowIdResponse();
            $pay_now_id_status = "";
            if (isset($pay_now_id_res)) {
                /**
                 * PayNowIDステータス取得
                 */
                $pay_now_id_status = $pay_now_id_res->getStatus();
            }

            // 成功
            if (TXN_SUCCESS_CODE === $txn_status) {
                var_export("success");
                die;
            } else if (TXN_PENDING_CODE === $txn_status) {
                var_export("pending");
                die;
                // 失敗
            } else if (TXN_FAILURE_CODE === $txn_status) {
                var_export("failure");
                die;
            } else {
                $page_title = ERROR_PAGE_TITLE;
                var_export("ddddddd");
                die;
            }
        }



        $pluginSetting = $this->util->getPluginSetting();
        $this->entityManager->getFilters()->enable('incomplete_order_status_hidden');
        // 編集
        if ($id) {
            $Customer = $this->customerRepository
                ->find($id);

            if (is_null($Customer)) {
                throw new NotFoundHttpException();
            }

            $oldStatusId = $Customer->getStatus()->getId();
            // 編集用にデフォルトパスワードをセット
            $previous_password = $Customer->getPassword();
            $Customer->setPassword($this->eccubeConfig['eccube_default_password']);
            // 新規登録
        } else {
            $Customer = $this->customerRepository->newCustomer();

            $oldStatusId = null;
        }

        // 会員登録フォーム
        $builder = $this->formFactory
            ->createBuilder(CustomerType::class, $Customer);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_EDIT_INDEX_INITIALIZE, $event);

        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            log_info('会員登録開始', [$Customer->getId()]);

            $encoder = $this->encoderFactory->getEncoder($Customer);

            if ($Customer->getPassword() === $this->eccubeConfig['eccube_default_password']) {
                $Customer->setPassword($previous_password);
            } else {
                if ($Customer->getSalt() === null) {
                    $Customer->setSalt($encoder->createSalt());
                    $Customer->setSecretKey($this->customerRepository->getUniqueSecretKey());
                }
                $Customer->setPassword($encoder->encodePassword($Customer->getPassword(), $Customer->getSalt()));
            }

            // 退会ステータスに更新の場合、ダミーのアドレスに更新
            $newStatusId = $Customer->getStatus()->getId();
            if ($oldStatusId != $newStatusId && $newStatusId == CustomerStatus::WITHDRAWING) {
                $Customer->setEmail(StringUtil::random(60) . '@dummy.dummy');
            }

            $this->entityManager->persist($Customer);
            $this->entityManager->flush();

            log_info('会員登録完了', [$Customer->getId()]);

            $event = new EventArgs(
                [
                    'form' => $form,
                    'Customer' => $Customer,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_EDIT_INDEX_COMPLETE, $event);

            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('admin_customer_edit', [
                'id' => $Customer->getId(),
            ]);
        }



        return [
            'form' => $form->createView(),
            'Customer' => $Customer,
            'tokenApiKey' => $pluginSetting['token_api_key']
        ];
    }
}
