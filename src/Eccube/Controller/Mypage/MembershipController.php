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

namespace Eccube\Controller\Mypage;

use Eccube\Controller\AbstractController;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Customer;
use Eccube\Entity\Product;
use Eccube\Entity\Order;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Exception\CartException;
use Eccube\Form\Type\Front\CustomerLoginType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CustomerFavoriteProductRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Service\CartService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Eccube\Repository\CustomerRepository;

class MembershipController extends AbstractController
{
     /**
     * コンテナ
     */
    protected $container;

     /**
     * customerRepository
     */
    protected $customerRepository;

    /**
     * 汎用処理用ユーティリティ
     */
    protected $util;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var CustomerFavoriteProductRepository
     */
    protected $customerFavoriteProductRepository;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;

    /**
     * MypageController constructor.
     *
     * @param OrderRepository $orderRepository
     * @param CustomerFavoriteProductRepository $customerFavoriteProductRepository
     * @param CartService $cartService
     * @param BaseInfoRepository $baseInfoRepository
     * @param PurchaseFlow $purchaseFlow
     */
    public function __construct(
        ContainerInterface $container,
        OrderRepository $orderRepository,
        CustomerFavoriteProductRepository $customerFavoriteProductRepository,
        CartService $cartService,
        BaseInfoRepository $baseInfoRepository,
        PurchaseFlow $purchaseFlow,
        CustomerRepository $customerRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->customerFavoriteProductRepository = $customerFavoriteProductRepository;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->cartService = $cartService;
        $this->purchaseFlow = $purchaseFlow;
        $this->container = $container;
        $this->util = $container->get('vt4g_plugin.service.util');
        $this->customerRepository = $customerRepository;
    }


    /**
     * membership.
     *
     * @Route("/product/detail/registration_prem/{id}", name="common_to_prem")
     * @Template("Mypage/membership.twig")
     */
    public function common_to_prem(Request $request, Product $Product)
    {
        $pluginSetting = $this->util->getPluginSetting();

       $productId = $Product->getId();
        $Customer = $this->getUser();

        $cc_info_bin = false;
        if (isset($request->get('customer_cus')['card_number'])) {
            if ($request->get('customer_cus')['card_number'] == "" || $request->get('customer_cus')['card_expire_month'] == "" || $request->get('customer_cus')['card_expire_year'] == "" || $request->get('customer_cus')['card_sec'] == "" || $request->get('customer_cus')['card_owner'] == "" || $request->get('customer_cus')['credit_token'] == "" ) {
                $cc_info_bin = false;
            } else {

                $cc_info_bin = true;
            }
        }
        $toPremCompleteMark = 0;
        $updateCardCompleteMark = 0;
        if($cc_info_bin==true){
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
            $card_info = $request->get("customer_cus");
            $customerId = $Customer->getId();
            $accountId = $customerId."_".time();
            $start_date_type = new \DateTime();
            $start_date = $start_date_type->format('Ymd');

            $request_data_acc = new \AccountAddRequestDto();
            $request_data_acc->setAccountId($accountId);
            $request_data_acc->setCreateDate($start_date);
            $transaction_acc = new \TGMDK_Transaction();
            $response_data_acc = $transaction_acc->execute($request_data_acc);
            
            $message_acc = $response_data_acc->getMerrMsg();
            $status_acc = $response_data_acc->getMStatus();
            if(TXN_SUCCESS_CODE === $status_acc){

                $request_data = new \RecurringAddRequestDto();
    
                $request_data->setToken($card_info['credit_token']);
                $request_data->setAccountId($accountId);
    
                $request_data->setGroupId($pluginSetting['recurring_group_id']);
                $request_data->setStartDate($start_date);
                // $request_data->setEndDate($card_info['endDate']);
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
                    
                    if (TXN_SUCCESS_CODE === $txn_status) {
                        if (TXN_SUCCESS_CODE === $pay_now_id_status) {
                            log_info('会員登録完了');
                            $Customer_ = $this->customerRepository->find($customerId);
                            $Customer_->setCusCustomerLevel(2);
                            $Customer_->setVt4gAccountId($accountId);
                            $this->entityManager->persist($Customer_);
                            $this->entityManager->flush();
    
                            $toPremCompleteMark = 1;
                                                  
                        } else {
                            $toPremCompleteMark = 0;
                            $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/products/detail/".$productId;
                            // var_export($pay_now_id_res->getMessage());
                            echo "<script>alert('" . $pay_now_id_res->getMessage() . "'); 
                            window.location.href='" . $url . "';
                        </script>";
                        }
                    } else if (TXN_PENDING_CODE === $txn_status) {
                        $toPremCompleteMark = 0;
                        $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/products/detail/".$productId;
                        // var_export($error_message);die;
                        echo "<script>alert('" . $error_message . "'); 
                            window.location.href='" . $url . "';
                        </script>";
                        // 失敗
                    } else if (TXN_FAILURE_CODE === $txn_status) {
                        $toPremCompleteMark = 0;
                        $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/products/detail/".$productId;
                        // var_export($error_message);die;
                        echo "<script>alert('" . $error_message . "'); 
                            window.location.href='" . $url . "';
                        </script>";
                    } else {
                        $toPremCompleteMark = 0;
                        $page_title = ERROR_PAGE_TITLE;
                        var_export($page_title);
                        die;
                    }
                }
            }else{
                $toPremCompleteMark = 0;
                $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/products/detail/".$productId;
                // var_export($error_message);die;
                echo "<script>alert('" . $message_acc . "'); 
                    window.location.href='" . $url . "';
                </script>";
            }




        }

        return [
            'Customer'=>$Customer,
            'toPremCompleteMark'=>$toPremCompleteMark,
            'updateCardCompleteMark'=>$updateCardCompleteMark
        ];
        

        
    }

    /**
     * membership.
     *
     * @Route("/mypage/membership", name="mypage_membership")
     * @Template("Mypage/membership.twig")
     */
    public function membership(Request $request)
    {
        // var_export("dfdfdfdfd");die;
        $pluginSetting = $this->util->getPluginSetting();
        $builder = $this->formFactory
            ->createNamedBuilder('', CustomerLoginType::class);
        $form = $builder->getForm();
        $Customer = $this->getUser();

        $cc_info_bin = false;
        if (isset($request->get('customer_cus')['card_number'])) {
            if ($request->get('customer_cus')['card_number'] == "" || $request->get('customer_cus')['card_expire_month'] == "" || $request->get('customer_cus')['card_expire_year'] == "" || $request->get('customer_cus')['card_sec'] == "" || $request->get('customer_cus')['card_owner'] == "" || $request->get('customer_cus')['credit_token'] == "" ) {
                $cc_info_bin = false;
            } else {

                $cc_info_bin = true;
            }
        }
        $toPremCompleteMark = 0;
        $updateCardCompleteMark = 0;
        if($cc_info_bin==true){
            define('ERROR_PAGE_TITLE', 'System Error');
            define('NORMAL_PAGE_TITLE', 'VeriTrans 4G - 会員カード管理サンプル画面');
            define('TXN_FAILURE_CODE', 'failure');
            define('TXN_PENDING_CODE', 'pending');
            define('TXN_SUCCESS_CODE', 'success');
            $order_id = "dummy" . time();
            $is_with_capture = "true";
            $jpo = "10";
            $payment_amount = $pluginSetting['recurring_amount']
            ;
            // require_once(MDK_DIR . "3GPSMDK.php");
            $card_info = $request->get("customer_cus");
            $customerId = $Customer->getId();
            $accountId = $customerId."_".time();
            $start_date_type = new \DateTime();
            $start_date = $start_date_type->format('Ymd');

            $request_data_acc = new \AccountAddRequestDto();
            $request_data_acc->setAccountId($accountId);
            $request_data_acc->setCreateDate($start_date);
            $transaction_acc = new \TGMDK_Transaction();
            $response_data_acc = $transaction_acc->execute($request_data_acc);
            
            $message_acc = $response_data_acc->getMerrMsg();
            $status_acc = $response_data_acc->getMStatus();
            if(TXN_SUCCESS_CODE === $status_acc){

                $request_data = new \RecurringAddRequestDto();
    
                $request_data->setToken($card_info['credit_token']);
                $request_data->setAccountId($accountId);
    
                $request_data->setGroupId($pluginSetting['recurring_group_id']);
                $request_data->setStartDate($start_date);
                // $request_data->setEndDate($card_info['endDate']);
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
    
                            $toPremCompleteMark = 1;
                                                  
                        } else {
                            $toPremCompleteMark = 0;
                            $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/mypage/membership";
                            // var_export($pay_now_id_res->getMessage());
                            echo "<script>alert('" . $pay_now_id_res->getMessage() . "'); 
                            window.location.href='" . $url . "';
                        </script>";
                        }
                    } else if (TXN_PENDING_CODE === $txn_status) {
                        $toPremCompleteMark = 0;
                        $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/mypage/membership";
                        // var_export($error_message);die;
                        echo "<script>alert('" . $error_message . "'); 
                            window.location.href='" . $url . "';
                        </script>";
                        // 失敗
                    } else if (TXN_FAILURE_CODE === $txn_status) {
                        $toPremCompleteMark = 0;
                        $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/mypage/membership";
                        // var_export($error_message);die;
                        echo "<script>alert('" . $error_message . "'); 
                            window.location.href='" . $url . "';
                        </script>";
                    } else {
                        $toPremCompleteMark = 0;
                        $page_title = ERROR_PAGE_TITLE;
                        var_export($page_title);
                        die;
                    }
                }
            }else{
                $toPremCompleteMark = 0;
                $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/mypage/membership";
                // var_export($error_message);die;
                echo "<script>alert('" . $message_acc . "'); 
                    window.location.href='" . $url . "';
                </script>";
            }
            
        }

        return [
            'Customer'=>$Customer,
            'tokenApiKey' => $pluginSetting['token_api_key'],
            'form' => $form->createView(),
            'toPremCompleteMark'=>$toPremCompleteMark,
            'updateCardCompleteMark'=>$updateCardCompleteMark
        ];
        

        
    }

    /**
     * membership.
     *
     * @Route("/mypage/membership/update_card", name="mypage_membership_update_card")
     * @Template("Mypage/membership.twig")
     */
    public function membership_udpate_card(Request $request)
    {
        $pluginSetting = $this->util->getPluginSetting();
        $builder = $this->formFactory
            ->createNamedBuilder('', CustomerLoginType::class);
        $form = $builder->getForm();
        $Customer = $this->getUser();

        $cc_info_bin = false;
        if (isset($request->get('customer_cus_update_card')['card_number'])) {
            if ($request->get('customer_cus_update_card')['card_number'] == "" || $request->get('customer_cus_update_card')['card_expire_month'] == "" || $request->get('customer_cus_update_card')['card_expire_year'] == "" || $request->get('customer_cus_update_card')['card_sec'] == "" || $request->get('customer_cus_update_card')['card_owner'] == "" || $request->get('customer_cus_update_card')['credit_token'] == "" ) {
                $cc_info_bin = false;
            } else {

                $cc_info_bin = true;
            }
        }
        $toPremCompleteMark = 0;
        $updateCardCompleteMark = 0;
        if($cc_info_bin==true){
            define('ERROR_PAGE_TITLE', 'System Error');
            define('NORMAL_PAGE_TITLE', 'VeriTrans 4G - 会員カード管理サンプル画面');
            define('TXN_FAILURE_CODE', 'failure');
            define('TXN_PENDING_CODE', 'pending');
            define('TXN_SUCCESS_CODE', 'success');

            $payment_amount = $pluginSetting['recurring_amount'];
            // require_once(MDK_DIR . "3GPSMDK.php");
            $card_info = $request->get("customer_cus_update_card");
            $customerId = $Customer->getId();
            $accountId = $Customer->getVt4gAccountId();
            $request_data = new \RecurringUpdateRequestDto();

            $request_data->setToken($card_info['credit_token']);
            $request_data->setAccountId($accountId);

            $request_data->setGroupId($pluginSetting['recurring_group_id']);
            // $start_date_type = new \DateTime();
            // $start_date = $start_date_type->format('Ymd');
            // $request_data->setStartDate($start_date);
            // $request_data->setEndDate($card_info['endDate']);
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
                
                if (TXN_SUCCESS_CODE === $txn_status) {
                    if (TXN_SUCCESS_CODE === $pay_now_id_status) {
                        log_info('会員登録完了');
                        $Customer_ = $this->customerRepository->find($customerId);
                        $Customer_->setCusCustomerLevel(2);
                        $this->entityManager->persist($Customer_);
                        $this->entityManager->flush();
                        $updateCardCompleteMark = 1;
                                              
                    } else {
                        $updateCardCompleteMark = 0;
                        $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/mypage/membership";
                        // var_export($pay_now_id_res->getMessage());
                        echo "<script>alert('" . $pay_now_id_res->getMessage() . "'); 
                        window.location.href='" . $url . "';
                    </script>";
                    }
                } else if (TXN_PENDING_CODE === $txn_status) {
                    $updateCardCompleteMark = 0;
                    $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/mypage/membership";
                    // var_export($error_message);die;
                    echo "<script>alert('" . $error_message . "'); 
                        window.location.href='" . $url . "';
                    </script>";
                    // 失敗
                } else if (TXN_FAILURE_CODE === $txn_status) {
                    $updateCardCompleteMark = 0;
                    $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/mypage/membership";
                    // var_export($error_message);die;
                    echo "<script>alert('" . $error_message . "'); 
                        window.location.href='" . $url . "';
                    </script>";
                } else {
                    $updateCardCompleteMark = 0;
                    $page_title = ERROR_PAGE_TITLE;
                    var_export($page_title);
                    die;
                }
            }
        }
        return [
            'Customer'=>$Customer,
            'tokenApiKey' => $pluginSetting['token_api_key'],
            'form' => $form->createView(),
            'updateCardCompleteMark'=>$updateCardCompleteMark,
            'toPremCompleteMark'=>$toPremCompleteMark
        ];
        

        
    }

    
}