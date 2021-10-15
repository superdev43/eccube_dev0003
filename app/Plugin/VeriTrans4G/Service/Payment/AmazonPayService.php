<?php
/*
 * Copyright (c) 2018 VeriTrans Inc., a Digital Garage company. All rights reserved.
 * http://www.veritrans.co.jp/
 */

namespace Plugin\VeriTrans4G\Service\Payment;

use Eccube\Entity\Customer;
use Plugin\VeriTrans4G\Entity\Vt4gOrderPayment;
use Plugin\VeriTrans4G\Form\Type\Shopping\PaymentAmazonPayType;

// Amazon Pay決済関連処理
class AmazonPayService extends BaseService
{
    /**
     * クレジットカード情報入力フォームを生成
     * @param  array $paymentInfo クレジットカード決済設定
     * @return object             クレジットカード情報入力フォーム
     */
    public function createAmazonPayForm($paymentInfo)
    {
        return $this->container->get('form.factory')
            ->create(PaymentAmazonPayType::class, compact('paymentInfo'));
    }


    /**
     * クレジットカード決済処理
     * (MDKトークン利用・再取引)
     *
     * @param  object  $inputs  フォーム入力データ
     * @param  array   $payload 追加参照データ
     * @param  array   &$error  エラー
     * @return boolean          決済が正常終了したか
     */
    public function commitNormalPayment($inputs, $payload, &$error)
    {
       
        // 決済金額 (整数値で設定するため小数点以下切り捨て)
        $amount = floor($payload['order']->getPaymentTotal());
        $is_with_capture = $inputs->get('payment_amazon_pay')['withCapture'];
        $is_suppress_shipping_address_view = $inputs->get('payment_amazon_pay')['suppressShippingAddressView'];
        $note_to_buyer = $inputs->get('payment_amazon_pay')['noteToBuyer'];
        
        $success_url = "http://localhost/eccube_shop/shopping/amazonpay/complete/".$payload['order']->getId();
        $cancel_url = "http://localhost/eccube_shop/card";
        $error_url = "http://localhost/eccube_shop/shopping/error";
        // カード情報登録フラグ
        $order_id = "amazonpay" . time();

        $request_data = new \AmazonpayAuthorizeRequestDto();
        $request_data->setOrderId($order_id);
        $request_data->setAmount($amount);
        $request_data->setWithCapture($is_with_capture);
        $request_data->setSuppressShippingAddressView($is_suppress_shipping_address_view);
        $request_data->setNoteToBuyer($note_to_buyer);
        $request_data->setSuccessUrl($success_url);
        $request_data->setCancelUrl($cancel_url);
        $request_data->setErrorUrl($error_url);
        $request_data->setAuthorizePushUrl("https://webhook.site/650fec36-2469-4e1f-be99-3c369efe1aa3");
        // $request_data->setSuccessUrl("https://webhook.site/650fec36-2469-4e1f-be99-3c369efe1aa3");
        // $request_data->setCancelUrl("https://webhook.site/650fec36-2469-4e1f-be99-3c369efe1aa3");
        // $request_data->setErrorUrl("https://webhook.site/650fec36-2469-4e1f-be99-3c369efe1aa3");
        // $request_data->setAuthorizePushUrl("https://webhook.site/650fec36-2469-4e1f-be99-3c369efe1aa3");
        // $request_data->setCapturePushUrl("https://webhook.site/650fec36-2469-4e1f-be99-3c369efe1aa3");
        // $request_data->setCancelPushUrl("https://webhook.site/650fec36-2469-4e1f-be99-3c369efe1aa3");
        // $request_data->setCancelPushUrl("https://webhook.site/650fec36-2469-4e1f-be99-3c369efe1aa3");

        /**
         * 実施
         */
        $transaction = new \TGMDK_Transaction();
        $response_data = $transaction->execute($request_data);

        //予期しない例外
        if (!isset($response_data)) {
            $page_title = ERROR_PAGE_TITLE;
        //想定応答の取得
        } else {
            define('TXN_SUCCESS_CODE', 'success');
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
        
            // ログ
            $test_log = "<!-- vResultCode=" . $txn_result_code . " -->";
            if (TXN_SUCCESS_CODE === $txn_status) {

                $orderId = $request_data->getOrderId();
                // 決済データを登録
                $payment = [
                    'orderId'    => $orderId,
                    'payStatus'  => '',
                    'cardType'   => '',
                    'cardAmount' => $amount,
                    'withCapture' => $payload['paymentInfo']['withCapture']
                ];
                $this->setOrderPayment($payload['order'], $payment, [], [], 'Nan');
                
                $this->handleNormalResponse($response_data,$payload['order'], $error);
                $this->em->commit();
                $isMpi = $payload['paymentInfo']['mpi_flg'];
                $this->mdkLogger->info(
                    sprintf(
                        $isMpi ? trans('vt4g_plugin.payment.shopping.mdk.start.mpi') : trans('vt4g_plugin.payment.shopping.mdk.start'),
                        $this->vt4gConst['VT4G_PAYNAME_PAYTYPEID_80']
                    )
                );
                // 成功
                $response_html = $response_data->getResponseContents();
                header("Content-type: text/html; charset=UTF-8");
                echo $response_html . $test_log;
                exit;
            } else {
                // エラーページ表示
                $title = "エラーページ";
                $html = $this->createResultPage($response_data, $title);
                print $html . $test_log;
                exit;
            }
        }



        // // 再取引決済の場合に元取引IDのバリデーションを行う
        // if ($isReTrade && !$this->isValidReTradeOrder($inputs->get('payment_order_id'), $payload['user']->getid())) {
        //     $error['payment'] = trans('vt4g_plugin.shopping.credit.mErrMsg.retrade').'<br/>';
        //     return false;
        // }

        // // MDKリクエスト生成・レスポンスのハンドリングに使用するデータ
        // $sources = array_merge(
        //     compact('isMpi'),
        //     compact('useAccountPayment'),
        //     compact('isReTrade'),
        //     compact('isAfterAuth'),
        //     compact('amount'),
        //     compact('inputs'),
        //     compact('doRegistCardinfo'),
        //     $payload
        // );

        // // MDKリクエストを生成
        // $mdkRequest = $this->makeMdkRequest($sources);

        // $orderId = $mdkRequest->getOrderId();
        // $sources['orderid'] = $orderId;

        // $cardType = $inputs->get('payment_credit')['payment_type'] ?? '';
        // if ($isAccountPayment) {
        //     $cardType = $inputs->get('payment_credit_account')['payment_type'] ?? '';
        // }
        // if ($isReTrade) {
        //     $cardType = $inputs->get('payment_credit_one_click')['payment_type'] ?? '';
        // }

        // // 決済データを登録
        // $payment = [
        //     'orderId'    => $orderId,
        //     'payStatus'  => '',
        //     'cardType'   => $cardType,
        //     'cardAmount' => $amount,
        //     'withCapture' => $payload['paymentInfo']['withCapture']
        // ];
        // $this->setOrderPayment($payload['order'], $payment, [], [], $inputs->get('token_id'));

        // $this->em->commit();

        // $this->mdkLogger->info(
        //     sprintf(
        //         $isMpi ? trans('vt4g_plugin.payment.shopping.mdk.start.mpi') : trans('vt4g_plugin.payment.shopping.mdk.start'),
        //         $this->vt4gConst['VT4G_PAYNAME_PAYTYPEID_10']
        //     )
        // );


        // $mdkTransaction = new \TGMDK_Transaction();
        // $mdkResponse = $mdkTransaction->execute($mdkRequest);

        // return $this->handleMdkResponse($mdkResponse, $sources, $error);
    }

    /**
     * MDKリクエストのレスポンスのハンドリング
     * (各パターン共通処理)
     *
     * @param  object  $response MDKリクエストのレスポンス
     * @param  array   $sources  ハンドリングに必要なデータ
     * @param  array   &$error   エラー表示用配列
     * @return boolean           レスポンスを正常に処理したかどうか
     */
    private function handleNormalResponse($response,$Order, &$error)
    {
        // // 通常クレジットカード決済の正常終了
        $this->paymentResult['isOK'] = true;
        // // 取引ID取得
        $this->paymentResult['orderId'] = $response->getOrderId();
        // // マスクされたクレジットカード番号
        // // $this->paymentResult['cardNumber'] = $response->getReqCardNumber();
        // // 支払い方法・支払い回数
        // $jpo = $response->getReqJpoInformation();
        // $this->paymentResult['paymentType'] = substr($jpo, 0, 2);
        // $this->paymentResult['paymentCount'] = substr($jpo, 2);

        // // 決済状態を保持
        // $this->paymentResult['payStatus'] = $sources['paymentInfo']['withCapture']
        //     ? $this->vt4gConst['VT4G_PAY_STATUS']['CAPTURE']['VALUE']
        //     : $this->vt4gConst['VT4G_PAY_STATUS']['AUTH']['VALUE'];
        // $this->paymentResult['mpiHosting'] = false;
        // $this->paymentResult['withCapture'] = $sources['paymentInfo']['withCapture'];

        $this->mdkLogger->info(print_r($this->paymentResult, true));

        // // 正常終了の場合
        // if ($this->paymentResult['isOK']) {
        //     // ベリトランス会員ID決済の場合
        //     if ($sources['useAccountPayment'] && !empty($sources['user']) && $sources['doRegistCardinfo']) {
        //         // ベリトランス会員IDをテーブルに保存
        //         $accountId = $response->getPayNowIdResponse()->getAccount()->getAccountId();
        //         $this->saveAccountId($sources['user']->getId(), $accountId);
        //     }

        // $this->completeAmazonOrder($Order);

        //     // 受注完了処理
        //     if (!$isCompleted) {
        //         $this->mdkLogger->fatal(trans('vt4g_plugin.shopping.credit.fatal.complete'));
        //         $error['payment'] = $this->defaultErrorMessage;
        //         return false;
        //     }
        // }

        return true;
    }

    /**
     * 受注完了処理
     *
     * @param  array $order 注文データ
     * @return void
     */
    public function completeAmazonOrder($response_suc, $order)
    {
        $this->paymentResult['isOK'] = true;
        $this->paymentResult['orderId'] = $response_suc->get('orderId');
        if (!$this->paymentResult['isOK']) {
            return false;
        }

        // 決済情報 (memo05)
        $payment = $this->paymentResult;
        
        // メール情報 (memo06)
        $this->mailData = [];
        $this->setMailTitle($this->vt4gConst['VT4G_PAYNAME_PAYTYPEID_80']);
        $this->setMailInfo('決済取引ID', $this->paymentResult['orderId']);
        $paymentMethod = $this->util->getPaymentMethod($order->getPayment()->getId());
        $this->setMailAdminSetting($paymentMethod);

        // 決済変更ログ情報 (plg_vt4g_order_logテーブル)
        $this->setLog($order);

        // // 受注ステータス更新
        // if (!$this->setNewOrderStatus($order,$payment['withCapture'])) {
        //     return false;
        // }

        // 受注完了処理
        $this->completeOrder($order, $payment, $this->logData, $this->mailData);

        return true;
    }


     /**
     * ログ出力内容を設定
     *
     * @param  object $order Orderクラスインスタンス
     * @return void
     */
    private function setLog($order, $paymentResult = null)
    {
        if (is_null($paymentResult)) {
            $paymentResult = $this->paymentResult;
        }

        $this->timeKey = '';

        $payId = $this->util->getPayId($order->getPayment()->getId());
        $payName = $this->util->getPayName($payId);
        // $payStatusName = $this->util->getPaymentStatusName($paymentResult['payStatus']);

        $this->setLogInfo('決済取引ID', $paymentResult['orderId']);
        $this->setLogInfo($payName, sprintf(
            $this->isPaymentRecv ? trans('決済結果通知受信') : trans('成功')
        ));
    }

    /**
     * ダミーモードを判定します。
     */
    public function createResultPage($response, $title) {

        $html = '<html>
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="Content-Language" content="ja" />
        <title>'.$title.'</title>
        <link href="../css/style.css" rel="stylesheet" type="text/css">
        </head>
        <body>
        <div class="system-message">
        <font size="2">
        本画面はVeriTrans4G Amazon Payの取引サンプル画面です。<br/>
        お客様ECサイトのショッピングカートとVeriTrans4Gとを連動させるための参考、例としてご利用ください。<br/>
        </font>
        </div>
        
        <div class="lhtitle">Amazon Pay：取引結果</div>
        <table border="0" cellpadding="0" cellspacing="0">
            <tr>
            <td class="rititletop">取引ID</td>
            <td class="rivaluetop">'.$response->getOrderId().'<br/></td>
            </tr>
            <tr>
            <td class="rititle">取引ステータス</td>
            <td class="rivalue">'.$response->getMStatus().'</td>
            </tr>
            <tr>
            <td class="rititle">結果コード</td>
            <td class="rivalue">'.$response->getVResultCode().'</td>
            </tr>
            <tr>
            <td class="rititle">結果メッセージ</td>
            <td class="rivalue">'.$response->getMerrMsg().'</td>
            </tr>
        </table>
        <br/>
        
        <a href="../PaymentMethodSelect.php">決済サンプルのトップメニューへ戻る</a>&nbsp;&nbsp;
        
        <hr>
        <img alt="VeriTransロゴ" src="../WEB-IMG/VeriTransLogo_WH.png">&nbsp; Copyright &copy; VeriTrans Inc. All rights reserved
        
        
        </body></html>';
    
        return $html;
    }




    /**
     * ダミーモードを判定します。
     * @return boolean||null trueとnull:ダミーモード、false:本番モード
     */
    protected function isDummyMode()
    {
        $subData = $this->util->getPluginSetting();
        if (isset($subData)) {
            return $subData['dummy_mode_flg'] == '1';
        } else {
            return true;
        }
    }
}
