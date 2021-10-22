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

namespace Plugin\CustomShipping\Controller;

use Eccube\Entity\BaseInfo;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Service\OrderHelper;
use Eccube\Form\Type\Front\EntryType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\CustomerStatusRepository;
use Eccube\Service\MailService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Eccube\Service\CartService;
use Eccube\Util\StringUtil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CusEntryController extends \Eccube\Controller\AbstractController
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
     * @var CustomerStatusRepository
     */
    protected $customerStatusRepository;

    /**
     * @var ValidatorInterface
     */
    protected $recursiveValidator;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var \Eccube\Service\CartService
     */
    protected $cartService;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * EntryController constructor.
     *
     * @param CartService $cartService
     * @param CustomerStatusRepository $customerStatusRepository
     * @param MailService $mailService
     * @param BaseInfoRepository $baseInfoRepository
     * @param CustomerRepository $customerRepository
     * @param EncoderFactoryInterface $encoderFactory
     * @param ValidatorInterface $validatorInterface
     * @param TokenStorageInterface $tokenStorage
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        ContainerInterface $container,
        CartService $cartService,
        CustomerStatusRepository $customerStatusRepository,
        MailService $mailService,
        BaseInfoRepository $baseInfoRepository,
        CustomerRepository $customerRepository,
        EncoderFactoryInterface $encoderFactory,
        ValidatorInterface $validatorInterface,
        TokenStorageInterface $tokenStorage,
        OrderHelper $orderHelper
    ) {
        $this->customerStatusRepository = $customerStatusRepository;
        $this->mailService = $mailService;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->customerRepository = $customerRepository;
        $this->encoderFactory = $encoderFactory;
        $this->recursiveValidator = $validatorInterface;
        $this->tokenStorage = $tokenStorage;
        $this->cartService = $cartService;
        $this->orderHelper = $orderHelper;
        $this->container = $container;
        $this->util = $container->get('vt4g_plugin.service.util');
    }

    /**
     * free会員登録画面.
     *
     * @Route("/entry", name="entry")
     * @Template("Entry/index.twig")
     */
    public function index(Request $request)
    {
        if ($this->isGranted('ROLE_USER')) {
            log_info('認証済のためログイン処理をスキップ');

            return $this->redirectToRoute('mypage');
        }

        /** @var $Customer \Eccube\Entity\Customer */
        $Customer = $this->customerRepository->newCustomer();

        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
        $builder = $this->formFactory->createBuilder(EntryType::class, $Customer);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_ENTRY_INDEX_INITIALIZE, $event);

        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            switch ($request->get('mode')) {
                case 'confirm':
                    log_info('会員登録確認開始');
                    log_info('会員登録確認完了');

                    return $this->render(
                        'Entry/confirm.twig',
                        [
                            'form' => $form->createView(),
                        ]
                    );

                case 'complete':
                    log_info('会員登録開始');

                    $encoder = $this->encoderFactory->getEncoder($Customer);
                    $salt = $encoder->createSalt();
                    $password = $encoder->encodePassword($Customer->getPassword(), $salt);
                    $secretKey = $this->customerRepository->getUniqueSecretKey();

                    $Customer
                        ->setSalt($salt)
                        ->setPassword($password)
                        ->setSecretKey($secretKey)
                        ->setPoint(0);
                    $Customer->setCusCustomerLevel(1);
                    $this->entityManager->persist($Customer);
                    $this->entityManager->flush();

                    log_info('会員登録完了');

                    $event = new EventArgs(
                        [
                            'form' => $form,
                            'Customer' => $Customer,
                        ],
                        $request
                    );
                    $this->eventDispatcher->dispatch(EccubeEvents::FRONT_ENTRY_INDEX_COMPLETE, $event);


                    $activateFlg = $this->BaseInfo->isOptionCustomerActivate();

                    // 仮会員設定が有効な場合は、確認メールを送信し完了画面表示.
                    if ($activateFlg) {
                        $activateUrl = $this->generateUrl('entry_activate', ['secret_key' => $Customer->getSecretKey()], UrlGeneratorInterface::ABSOLUTE_URL);

                        // メール送信
                        $this->mailService->sendCustomerConfirmMail($Customer, $activateUrl);

                        if ($event->hasResponse()) {
                            return $event->getResponse();
                        }

                        log_info('仮会員登録完了画面へリダイレクト');

                        return $this->redirectToRoute('entry_complete');
                    } else {
                        // 仮会員設定が無効な場合は、会員登録を完了させる.
                        $qtyInCart = $this->entryActivate($request, $Customer->getSecretKey());

                        // URLを変更するため完了画面にリダイレクト
                        return $this->redirectToRoute('entry_activate', [
                            'secret_key' => $Customer->getSecretKey(),
                            'qtyInCart' => $qtyInCart,
                        ]);
                    }
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }
    /**
     * prem会員登録画面.
     *
     * @Route("/entry_prem", name="entry_prem")
     * @Template("Entry/index_prem.twig")
     */
    public function cus_index(Request $request)
    {
        $pluginSetting = $this->util->getPluginSetting();
        if ($this->isGranted('ROLE_USER')) {
            log_info('認証済のためログイン処理をスキップ');

            return $this->redirectToRoute('mypage');
        }

        /** @var $Customer \Eccube\Entity\Customer */
        $Customer = $this->customerRepository->newCustomer();

        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
        $builder = $this->formFactory->createBuilder(EntryType::class, $Customer);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_ENTRY_INDEX_INITIALIZE, $event);

        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();

        $form->handleRequest($request);
        $cc_info_bin = false;
        if (isset($request->get('customer_cus')['card_number'])) {
            if ($request->get('customer_cus')['card_number'] == "" || $request->get('customer_cus')['card_expire_month'] == "" || $request->get('customer_cus')['card_expire_year'] == "" || $request->get('customer_cus')['card_sec'] == "" || $request->get('customer_cus')['card_owner'] == "" || $request->get('customer_cus')['credit_token'] == "" ) {
                $cc_info_bin = false;
            } else {

                $cc_info_bin = true;
            }
        }
        if (isset($request->get('comp_customer_cus')['card_number'])) {
            if ($request->get('comp_customer_cus')['card_number'] == "" || $request->get('comp_customer_cus')['card_expire_month'] == "" || $request->get('comp_customer_cus')['card_expire_year'] == "" || $request->get('comp_customer_cus')['card_sec'] == "" || $request->get('comp_customer_cus')['card_owner'] == "" || $request->get('comp_customer_cus')['credit_token'] == "" ) {
                $cc_info_bin = false;
            } else {

                $cc_info_bin = true;
            }
        }

        if ($form->isSubmitted() && $form->isValid() && $cc_info_bin == true) {
            switch ($request->get('mode')) {
                case 'confirm':
                    log_info('会員登録確認開始');
                    log_info('会員登録確認完了');

                    return $this->render(
                        'Entry/confirm_prem.twig',
                        [
                            'form' => $form->createView(),
                            'cc_info' => $request->get('customer_cus'),
                            'recurringAmount' => $pluginSetting['recurring_amount']
                        ]
                    );

                case 'complete':
                    log_info('会員登録開始');

                    $encoder = $this->encoderFactory->getEncoder($Customer);
                    $salt = $encoder->createSalt();
                    $password = $encoder->encodePassword($Customer->getPassword(), $salt);
                    $secretKey = $this->customerRepository->getUniqueSecretKey();
                    $payment_amount = $pluginSetting['recurring_amount'];
                    $start_date_type = new \DateTime();
                    $start_date = $start_date_type->format('Ymd');
                    $Customer
                        ->setSalt($salt)
                        ->setRecurringAmount($payment_amount)
                        ->setRecurringStartDatetime($start_date_type)
                        ->setLastTimeRecurringStatus(1)
                        ->setLastTimeRecurringDatetime($start_date_type)
                        ->setRecurringMethod('クレジットカード')
                        ->setPassword($password)
                        ->setSecretKey($secretKey)
                        ->setPoint(0);

                    $this->entityManager->persist($Customer);
                    $this->entityManager->flush();


                    define('ERROR_PAGE_TITLE', 'System Error');
                    define('NORMAL_PAGE_TITLE', 'VeriTrans 4G - 会員カード管理サンプル画面');
                    define('TXN_FAILURE_CODE', 'failure');
                    define('TXN_PENDING_CODE', 'pending');
                    define('TXN_SUCCESS_CODE', 'success');
                    $order_id = "dummy" . time();
                    $is_with_capture = "true";
                    $jpo = "10";
                    
                    
                    // require_once(MDK_DIR . "3GPSMDK.php");
                    $card_info = $request->get("comp_customer_cus");
                    $customerId = $Customer->getId();
                    $accountId = $Customer->getId()."_".time();
                    $start_date_type = new \DateTime();
                    $start_date = $start_date_type->format('Ymd');

                    $end_date_type = new \DateTime('2021-11-20 17:52:17');
                    $end_date = $end_date_type->format('Ymd');

                    // $request_data_acc->setCreateDate($start_date);
                    $request_data_acc = new \AccountAddRequestDto();
                    $request_data_acc->setAccountId($accountId);
                    $transaction_acc = new \TGMDK_Transaction();
                    $response_data_acc = $transaction_acc->execute($request_data_acc);
                    
                    $message_acc = $response_data_acc->getMerrMsg();
                    $status_acc = $response_data_acc->getMStatus();

                    if(TXN_SUCCESS_CODE === $status_acc){
                        $request_data = new \RecurringAddRequestDto();
    
                        $request_data->setToken($card_info['credit_token']);
                        $request_data->setAccountId($accountId);
                        $request_data->setGroupId($pluginSetting['recurring_group_id']);
                        // $request_data->setFinalCharge(0);
                        // $request_data->setSalesDay('20211021');

                        // var_export($request_data->getGroupId());die;
                        // $request_data->setGroupId('Veritrans_Dummy_02');
                        // $request_data->setChargeType(1);//new

                        
                        $request_data->setStartDate($start_date);
                        $request_data->setEndDate($end_date);
                        $request_data->setOneTimeAmount($payment_amount);
                        $request_data->setAmount($payment_amount);
                        // var_export($request_data);die;
    
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
    
                                if (TXN_SUCCESS_CODE === $pay_now_id_status) {
                                    log_info('会員登録完了');
                                    $Customer_ = $this->customerRepository->find($customerId);
                                    $Customer_->setCusCustomerLevel(2);
                                    $Customer_->setVt4gAccountId($accountId);
                                    $Customer_->setRecurringAmount($payment_amount);
                                    $Customer_->setLastTimeRecurringStatus(1);
                                    $Customer_->setLastTimeRecurringDatetime($start_date_type);
                                    $Customer_->setRecurringMethod("クレジットカード");
                                    $Customer_->setRecurringStartDatetime($start_date_type);
                                    
                                    $this->entityManager->persist($Customer_);
                                    $this->entityManager->flush();
    
    
                                    $event = new EventArgs(
                                        [
                                            'form' => $form,
                                            'Customer' => $Customer,
                                        ],
                                        $request
                                    );
                                    $this->eventDispatcher->dispatch(EccubeEvents::FRONT_ENTRY_INDEX_COMPLETE, $event);
    
    
                                    $activateFlg = $this->BaseInfo->isOptionCustomerActivate();
    
                                    // 仮会員設定が有効な場合は、確認メールを送信し完了画面表示.
                                    if ($activateFlg) {
                                        $activateUrl = $this->generateUrl('entry_activate', ['secret_key' => $Customer->getSecretKey()], UrlGeneratorInterface::ABSOLUTE_URL);
    
                                        // メール送信
                                        $this->mailService->sendCustomerConfirmMail($Customer_, $activateUrl);
    
                                        if ($event->hasResponse()) {
                                            return $event->getResponse();
                                        }
    
                                        log_info('仮会員登録完了画面へリダイレクト');
    
                                        return $this->redirectToRoute('entry_complete');
                                    } else {
                                        // 仮会員設定が無効な場合は、会員登録を完了させる.
                                        $qtyInCart = $this->entryActivate($request, $Customer->getSecretKey());
    
                                        // URLを変更するため完了画面にリダイレクト
                                        return $this->redirectToRoute('entry_activate', [
                                            'secret_key' => $Customer->getSecretKey(),
                                            'qtyInCart' => $qtyInCart,
                                        ]);
                                    }
                                } else {
                                    $Customer__ = $this->customerRepository->find($customerId);
                                    $em = $this->getDoctrine()->getManager();
                                    $em->remove($Customer__);
                                    $em->flush();
                                    $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/entry_prem";
                                    // var_export($pay_now_id_res->getMessage());
                                    echo "<script>alert('" . $pay_now_id_res->getMessage() . "'); 
                                    window.location.href='" . $url . "';
                                </script>";
                                }
                            } else if (TXN_PENDING_CODE === $txn_status) {
                                $Customer__ = $this->customerRepository->find($customerId);
                                $em = $this->getDoctrine()->getManager();
                                $em->remove($Customer__);
                                $em->flush();
                                $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/entry_prem";
                                // var_export($error_message);die;
                                echo "<script>alert('" . $response_data . "'); 
                                    window.location.href='" . $url . "';
                                </script>";
                                // 失敗
                            } else if (TXN_FAILURE_CODE === $txn_status) {
                                $Customer__ = $this->customerRepository->find($customerId);
                                $em = $this->getDoctrine()->getManager();
                                $em->remove($Customer__);
                                $em->flush();
                                $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/entry_prem";
                                // var_export($error_message);die;
                                echo "<script>alert('" . $response_data . "'); 
                                    window.location.href='" . $url . "';
                                </script>";
                            } else {
                                $page_title = ERROR_PAGE_TITLE;
                                var_export($page_title);
                                die;
                            }
                        }
                    }else{
                        $Customer__ = $this->customerRepository->find($customerId);
                        $em = $this->getDoctrine()->getManager();
                        $em->remove($Customer__);
                        $em->flush();
                        $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/entry_prem";
                        // var_export($error_message);die;
                        echo "<script>alert('" . $message_acc . "'); 
                            window.location.href='" . $url . "';
                        </script>";
                    }

            }
        }

        return [
            'form' => $form->createView(),
            'tokenApiKey' => $pluginSetting['token_api_key'],
            'recurringAmount' => $pluginSetting['recurring_amount']
        ];
    }
    /**
     * prem会員登録画面admin.
     *
     * @Route("/customer_prem/admin/cancel/{id}", name="admin_customer_prem_canel")
     * @Template("Entry/index_prem.twig")
     */
    public function customer_prem_canel_admin(Request $request, Customer $Customer)
    {
        $pluginSetting = $this->util->getPluginSetting();
        define('PAY_NOW_ID_FAILURE_CODE', 'failure');
        define('PAY_NOW_ID_PENDING_CODE', 'pending');
        define('PAY_NOW_ID_SUCCESS_CODE', 'success');

        $customerId = $Customer->getId();
        $accountId = $Customer->getVt4gAccountID();
        $exec_mode = 2; //1:get subscription information, 2:delete subscription
        $group_id = $pluginSetting['recurring_group_id'];
        $end_date_type = new \DateTime();
        $end_date = $end_date_type->format('Ymd');
        $final_charge = 1;

        $request_data = new \RecurringDeleteRequestDto();
        $request_data->setAccountId($accountId);
        $request_data->setGroupId($group_id);
        // $request_data->setEndDate($end_date);
        $request_data->setFinalCharge($final_charge);

        $transaction = new \TGMDK_Transaction();
        $response_data = $transaction->execute($request_data);
        if (!isset($response_data)) {
            $page_title = ERROR_PAGE_TITLE;
            //想定応答の取得
        } else {
            $page_title = NORMAL_PAGE_TITLE;
            /**
             * PayNowIDレスポンス取得
             */
            $pay_now_id_res = $response_data->getPayNowIdResponse();

            $process_id = "";
            $pay_now_id_status = "";

            if (isset($pay_now_id_res)) {
                /**
                 * PayNowID処理番号取得
                 */
                $process_id = $pay_now_id_res->getProcessId();
                /**
                 * PayNowIDステータス取得
                 */
                $pay_now_id_status = $pay_now_id_res->getStatus();
            }

            /**
             * 詳細コード取得
             */
            $txn_result_code = $response_data->getVResultCode();
            /**
             * エラーメッセージ取得
             */
            $error_message = $response_data->getMerrMsg();
            if (PAY_NOW_ID_SUCCESS_CODE === $pay_now_id_status) {
                $readyCustomer = $Customer;
                $readyCustomer->setCusCustomerLevel(1);
                $readyCustomer->setVt4gAccountId("");
                $em = $this->getDoctrine()->getManager();
                $em->persist($readyCustomer);
                $em->flush();

                return $this->redirectToRoute('admin_customer');
            } else if (PAY_NOW_ID_PENDING_CODE === $pay_now_id_status) {
                $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/EC1027/customer";
                // var_export($error_message);die;
                echo "<script>alert('" . $error_message . "'); 
                    window.location.href='" . $url . "';
                </script>";
            } else if (PAY_NOW_ID_FAILURE_CODE === $pay_now_id_status) {
                $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/EC1027/customer";
                // var_export($error_message);die;
                echo "<script>alert('" . $error_message . "'); 
                    window.location.href='" . $url . "';
                </script>";
            } else {
                $page_title = ERROR_PAGE_TITLE;
                var_export($page_title);
               
                return $this->redirectToRoute('admin_customer');
            }
        }       
    }

    /**
     * 退会画面.
     *
     * @Route("/mypage/withdraw", name="mypage_withdraw")
     * @Template("Mypage/withdraw.twig")
     */
    public function withdraw_cus(Request $request)
    {
        $pluginSetting = $this->util->getPluginSetting();
        $builder = $this->formFactory->createBuilder();

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_WITHDRAW_INDEX_INITIALIZE, $event);

        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            switch ($request->get('mode')) {
                case 'confirm':
                    log_info('退会確認画面表示');

                    return $this->render(
                        'Mypage/withdraw_confirm.twig',
                        [
                            'form' => $form->createView(),
                        ]
                    );

                case 'complete':
                    log_info('退会処理開始');

                    /* @var $Customer \Eccube\Entity\Customer */
                    $Customer = $this->getUser();

                    $accountId = $Customer->getVt4gAccountID();
                    $exec_mode = 2; //1:get subscription information, 2:delete subscription
                    $group_id = $pluginSetting['recurring_group_id'];
                    $end_date_type = new \DateTime();
                    $end_date = $end_date_type->format('Ymd');
                    $final_charge = 1;
                    $request_data = new \RecurringDeleteRequestDto();
                    $request_data->setAccountId($accountId);
                    $request_data->setGroupId($group_id);
                    // $request_data->setEndDate($end_date);
                    $request_data->setFinalCharge($final_charge);

                    $transaction = new \TGMDK_Transaction();
                    $response_data = $transaction->execute($request_data);
                    if (!isset($response_data)) {
                        $page_title = ERROR_PAGE_TITLE;
                        //想定応答の取得
                    } else {
                        $page_title = NORMAL_PAGE_TITLE;
                        /**
                         * PayNowIDレスポンス取得
                         */
                        $pay_now_id_res = $response_data->getPayNowIdResponse();

                        $process_id = "";
                        $pay_now_id_status = "";

                        if (isset($pay_now_id_res)) {
                            /**
                             * PayNowID処理番号取得
                             */
                            $process_id = $pay_now_id_res->getProcessId();
                            /**
                             * PayNowIDステータス取得
                             */
                            $pay_now_id_status = $pay_now_id_res->getStatus();
                        }

                        /**
                         * 詳細コード取得
                         */
                        $txn_result_code = $response_data->getVResultCode();
                        /**
                         * エラーメッセージ取得
                         */
                        $error_message = $response_data->getMerrMsg();
                        
                        if ("success" === $pay_now_id_status) {
                            $readyCustomer = $Customer;
                            $readyCustomer->setCusCustomerLevel(1);
                            $readyCustomer->setVt4gAccountId("");
                            $em = $this->getDoctrine()->getManager();
                            $em->persist($readyCustomer);
                            $em->flush();


                            $email = $Customer->getEmail();

                            // 退会ステータスに変更
                            $CustomerStatus = $this->customerStatusRepository->find(CustomerStatus::WITHDRAWING);
                            $Customer->setStatus($CustomerStatus);
                            $Customer->setEmail(StringUtil::random(60) . '@dummy.dummy');

                            $this->entityManager->flush();

                            log_info('退会処理完了');

                            $event = new EventArgs(
                                [
                                    'form' => $form,
                                    'Customer' => $Customer,
                                ],
                                $request
                            );
                            $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_WITHDRAW_INDEX_COMPLETE, $event);

                            // メール送信
                            $this->mailService->sendCustomerWithdrawMail($Customer, $email);

                            // カートと受注のセッションを削除
                            $this->cartService->clear();
                            $this->orderHelper->removeSession();

                            // ログアウト
                            $this->tokenStorage->setToken(null);

                            log_info('ログアウト完了');

                            return $this->redirectToRoute('mypage_withdraw_complete');
                        } else if (PAY_NOW_ID_PENDING_CODE === $pay_now_id_status) {
                            $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/EC1027/customer";
                            // var_export($error_message);die;
                            echo "<script>alert('" . $error_message . "'); 
                                window.location.href='" . $url . "';
                            </script>";
                        } else if (PAY_NOW_ID_FAILURE_CODE === $pay_now_id_status) {
                            $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/EC1027/customer";
                            // var_export($error_message);die;
                            echo "<script>alert('" . $error_message . "'); 
                                window.location.href='" . $url . "';
                            </script>";
                        } else {
                            $page_title = ERROR_PAGE_TITLE;
                            var_export($page_title);

                            return $this->redirectToRoute('mypage_withdraw_complete');
                        }
                    }
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * common.
     *
     * @Route("/customer_prem/cancel/{id}", name="common_customer_prem_canel")
     * @Template("Entry/index_prem.twig")
     */
    public function customer_prem_canel(Request $request, Customer $Customer)
    {
        $pluginSetting = $this->util->getPluginSetting();

        define('PAY_NOW_ID_FAILURE_CODE', 'failure');
        define('PAY_NOW_ID_PENDING_CODE', 'pending');
        define('PAY_NOW_ID_SUCCESS_CODE', 'success');
        $customerId = $Customer->getId();
        $accountId = $Customer->getVt4gAccountID();

        $exec_mode = 2; //1:get subscription information, 2:delete subscription
        $group_id = $pluginSetting['recurring_group_id'];
        $end_date_type = new \DateTime();
        $end_date = $end_date_type->format('Ymd');
        $final_charge = 1;

        $request_data = new \RecurringDeleteRequestDto();
        $request_data->setAccountId($accountId);
        $request_data->setGroupId($group_id);
        // $request_data->setEndDate($end_date);
        $request_data->setFinalCharge($final_charge);

        $transaction = new \TGMDK_Transaction();
        $response_data = $transaction->execute($request_data);
        if (!isset($response_data)) {
            $page_title = ERROR_PAGE_TITLE;
            //想定応答の取得
        } else {
            $page_title = NORMAL_PAGE_TITLE;
            /**
             * PayNowIDレスポンス取得
             */
            $pay_now_id_res = $response_data->getPayNowIdResponse();

            $process_id = "";
            $pay_now_id_status = "";

            if (isset($pay_now_id_res)) {
                /**
                 * PayNowID処理番号取得
                 */
                $process_id = $pay_now_id_res->getProcessId();
                /**
                 * PayNowIDステータス取得
                 */
                $pay_now_id_status = $pay_now_id_res->getStatus();
            }

            /**
             * 詳細コード取得
             */
            $txn_result_code = $response_data->getVResultCode();
            /**
             * エラーメッセージ取得
             */
            
            $error_message = $response_data->getMerrMsg();
            if (PAY_NOW_ID_SUCCESS_CODE === $pay_now_id_status) {
                $readyCustomer = $Customer;
                $readyCustomer->setCusCustomerLevel(1);
                $readyCustomer->setVt4gAccountId("");
                $readyCustomer->setRecurringAmount(null);
                $readyCustomer->setLastTimeRecurringStatus(0);
                $readyCustomer->setLastTimeRecurringDatetime(null);
                $readyCustomer->setRecurringMethod("クレジットカード");
                $readyCustomer->setRecurringStartDatetime(null);
                $em = $this->getDoctrine()->getManager();
                $em->persist($readyCustomer);
                $em->flush();

                return $this->redirectToRoute('mypage_membership');
            } else if (PAY_NOW_ID_PENDING_CODE === $pay_now_id_status) {
                $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/mypage/membership";
                // var_export($error_message);die;
                echo "<script>alert('" . $error_message . "'); 
                    window.location.href='" . $url . "';
                </script>";
            } else if (PAY_NOW_ID_FAILURE_CODE === $pay_now_id_status) {
                $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/mypage/membership";
                // var_export($error_message);die;
                echo "<script>alert('" . $error_message . "'); 
                    window.location.href='" . $url . "';
                </script>";
            } else {
                $page_title = ERROR_PAGE_TITLE;
                var_export($page_title);
               
                return $this->redirectToRoute('mypage_membership');
            }
        }       
    }

    /**
     * 会員登録完了画面.
     *
     * @Route("/entry/complete", name="entry_complete")
     * @Template("Entry/complete.twig")
     */
    public function complete()
    {
        return [];
    }

    /**
     * 会員のアクティベート（本会員化）を行う.
     *
     * @Route("/entry/activate/{secret_key}/{qtyInCart}", name="entry_activate")
     * @Template("Entry/activate.twig")
     */
    public function activate(Request $request, $secret_key, $qtyInCart = null)
    {
        $errors = $this->recursiveValidator->validate(
            $secret_key,
            [
                new Assert\NotBlank(),
                new Assert\Regex(
                    [
                        'pattern' => '/^[a-zA-Z0-9]+$/',
                    ]
                ),
            ]
        );

        if (!is_null($qtyInCart)) {

            return [
                'qtyInCart' => $qtyInCart,
            ];
        } elseif ($request->getMethod() === 'GET' && count($errors) === 0) {

            // 会員登録処理を行う
            $qtyInCart = $this->entryActivate($request, $secret_key);

            return [
                'qtyInCart' => $qtyInCart,
            ];
        }

        throw new HttpException\NotFoundHttpException();
    }


    /**
     * 会員登録処理を行う
     *
     * @param Request $request
     * @param $secret_key
     * @return \Eccube\Entity\Cart|mixed
     */
    private function entryActivate(Request $request, $secret_key)
    {
        log_info('本会員登録開始');
        $Customer = $this->customerRepository->getProvisionalCustomerBySecretKey($secret_key);
        if (is_null($Customer)) {
            throw new HttpException\NotFoundHttpException();
        }

        $CustomerStatus = $this->customerStatusRepository->find(CustomerStatus::REGULAR);
        $Customer->setStatus($CustomerStatus);
        $this->entityManager->persist($Customer);
        $this->entityManager->flush();

        log_info('本会員登録完了');

        $event = new EventArgs(
            [
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_ENTRY_ACTIVATE_COMPLETE, $event);

        // メール送信
        $this->mailService->sendCustomerCompleteMail($Customer);

        // Assign session carts into customer carts
        $Carts = $this->cartService->getCarts();
        $qtyInCart = 0;
        foreach ($Carts as $Cart) {
            $qtyInCart += $Cart->getTotalQuantity();
        }

        // 本会員登録してログイン状態にする
        $token = new UsernamePasswordToken($Customer, null, 'customer', ['ROLE_USER']);
        $this->tokenStorage->setToken($token);
        $request->getSession()->migrate(true);

        if ($qtyInCart) {
            $this->cartService->save();
        }

        log_info('ログイン済に変更', [$this->getUser()->getId()]);

        return $qtyInCart;
    }
}
