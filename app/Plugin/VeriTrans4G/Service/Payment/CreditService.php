<?php
/*
 * Copyright (c) 2018 VeriTrans Inc., a Digital Garage company. All rights reserved.
 * http://www.veritrans.co.jp/
 */

namespace Plugin\VeriTrans4G\Service\Payment;

use Eccube\Entity\Customer;
use Plugin\VeriTrans4G\Entity\Vt4gOrderPayment;
use Plugin\VeriTrans4G\Form\Type\Shopping\PaymentCreditType;
use Plugin\VeriTrans4G\Form\Type\Shopping\PaymentCreditAccountType;
use Plugin\VeriTrans4G\Form\Type\Shopping\PaymentCreditOneClickType;

// クレジットカード決済関連処理
class CreditService extends BaseService
{
    /**
     * クレジットカード情報入力フォームを生成
     * @param  array $paymentInfo クレジットカード決済設定
     * @return object             クレジットカード情報入力フォーム
     */
    public function createCreditForm($paymentInfo)
    {
        return $this->container->get('form.factory')
            ->create(PaymentCreditType::class, compact('paymentInfo'));
    }

    /**
     * ベリトランス会員ID決済用の入力フォームを生成
     *
     * @param  array $paymentInfo クレジットカード決済設定
     * @return object             ベリトランス会員ID決済用入力フォーム
     */
    public function createAccountForm($paymentInfo)
    {
        return $this->container->get('form.factory')
            ->create(PaymentCreditAccountType::class, compact('paymentInfo'));
    }

    /**
     * 再取引用の入力フォームを生成
     *
     * @param  array $paymentInfo クレジットカード決済設定
     * @return object             再取引用入力フォーム
     */
    public function createOneClickForm($paymentInfo)
    {
        return $this->container->get('form.factory')
            ->create(PaymentCreditOneClickType::class, compact('paymentInfo'));
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
        // 本人認証フラグ
        $isMpi = $payload['paymentInfo']['mpi_flg'];
        // ベリトランス会員ID決済の有効設定フラグ
        $useAccountPayment = $payload['paymentInfo']['one_click_flg'] === $this->vt4gConst['VT4G_CREDIT_ONE_CLICK']['VERITRANS_ID'];
        // ベリトランス会員ID決済フラグ
        $isAccountPayment = $payload['mode'] === 'account';
        // 再取引フラグ
        $isReTrade = $payload['mode'] === 'retrade';
        // 本人認証後フラグ
        $isAfterAuth = false;
        // 決済金額 (整数値で設定するため小数点以下切り捨て)
        $amount = floor($payload['order']->getPaymentTotal());
        // カード情報登録フラグ
        $doRegistCardinfo = $isAccountPayment || (isset($inputs->get('payment_credit')['cardinfo_regist']) && $inputs->get('payment_credit')['cardinfo_regist'] === '1');

        // 再取引決済の場合に元取引IDのバリデーションを行う
        if ($isReTrade && !$this->isValidReTradeOrder($inputs->get('payment_order_id'), $payload['user']->getid())) {
            $error['payment'] = trans('vt4g_plugin.shopping.credit.mErrMsg.retrade').'<br/>';
            return false;
        }

        // MDKリクエスト生成・レスポンスのハンドリングに使用するデータ
        $sources = array_merge(
            compact('isMpi'),
            compact('useAccountPayment'),
            compact('isReTrade'),
            compact('isAfterAuth'),
            compact('amount'),
            compact('inputs'),
            compact('doRegistCardinfo'),
            $payload
        );

        // MDKリクエストを生成
        $mdkRequest = $this->makeMdkRequest($sources);

        $orderId = $mdkRequest->getOrderId();
        $sources['orderid'] = $orderId;

        $cardType = $inputs->get('payment_credit')['payment_type'] ?? '';
        if ($isAccountPayment) {
            $cardType = $inputs->get('payment_credit_account')['payment_type'] ?? '';
        }
        if ($isReTrade) {
            $cardType = $inputs->get('payment_credit_one_click')['payment_type'] ?? '';
        }

        // 決済データを登録
        $payment = [
            'orderId'    => $orderId,
            'payStatus'  => '',
            'cardType'   => $cardType,
            'cardAmount' => $amount,
            'withCapture' => $payload['paymentInfo']['withCapture']
        ];
        $this->setOrderPayment($payload['order'], $payment, [], [], $inputs->get('token_id'));

        $this->em->commit();

        $this->mdkLogger->info(
            sprintf(
                $isMpi ? trans('vt4g_plugin.payment.shopping.mdk.start.mpi') : trans('vt4g_plugin.payment.shopping.mdk.start'),
                $this->vt4gConst['VT4G_PAYNAME_PAYTYPEID_10']
            )
        );


        $mdkTransaction = new \TGMDK_Transaction();
        $mdkResponse = $mdkTransaction->execute($mdkRequest);

        return $this->handleMdkResponse($mdkResponse, $sources, $error);
    }

    /**
     * クレジットカード決済処理
     * (本人認証リダイレクト)
     *
     * @param  object  $request リクエストデータ
     * @param  array   $payload 追加参照データ
     * @param  array   &$error  エラー
     * @return boolean          決済が正常終了したか
     */
    public function commitMpiPayment($request, $payload, &$error)
    {
        // ベリトランス会員ID決済フラグ
        $useAccountPayment = $payload['paymentInfo']['one_click_flg'] === $this->vt4gConst['VT4G_CREDIT_ONE_CLICK']['VERITRANS_ID'];

        // リクエストID
        $requestId = htmlspecialchars($request->get('RequestId'));
        if (empty($requestId)) {
            $this->mdkLogger->fatal(trans('vt4g_plugin.shopping.credit.fatal.request.id'));
            $error['payment'] = $this->getErrorMessage();
            return false;
        }

        $mdkRequest = new \SearchRequestDto();

        // リクエストID
        $mdkRequest->setRequestId($requestId);

        // リクエスト実行
        $this->mdkLogger->info(trans('vt4g_plugin.payment.shopping.mpi.payment.start'));
        $mdkTransaction = new \TGMDK_Transaction();
        $mdkResponse = $mdkTransaction->execute($mdkRequest);

        $sources = [
            'isMpi'             => true,
            'useAccountPayment' => $useAccountPayment,
            'isAfterAuth'       => true,
            'order'             => $payload['order'],
            'paymentInfo'       => $payload['paymentInfo'],
            'user'              => $payload['user']
        ];

        return $this->handleMdkResponse($mdkResponse, $sources, $error);
    }

    /**
     * 売上処理
     *
     * @param  array $payload 売上処理に使用するデータ
     * @return array          売上処理結果データ
     */
    public function operateCapture($payload)
    {
        // 決済ステータス
        $paymentStatus = $payload['orderPayment']->getMemo04();
        // 決済申込時のレスポンス
        $prevPaymentResult = unserialize($payload['orderPayment']->getMemo05());

        // レスポンス初期化
        $authOperationResult = $this->initPaymentResult();

        // 決済ステータスが売上の場合
        if ($paymentStatus == $this->vt4gConst['VT4G_PAY_STATUS']['CAPTURE']['VALUE']) {
            // 現在の取引ID
            $originPaymentOrderId = $payload['orderPayment']->getMemo01();

            // 決済ログ情報を初期化
            $this->logData = [];

            // 再決済
            $authOperationResult = $this->operateAuth($payload, $this->vt4gConst['VT4G_PAY_STATUS']['CAPTURE']['VALUE']);

            if (!$authOperationResult['isOK']) {
                $authOperationResult['message'] = $authOperationResult['vResultCode'].':'.$authOperationResult['mErrMsg'];

                $this->mdkLogger->info(print_r($authOperationResult, true));

                return $authOperationResult;
            }

            // 更新処理
            $this->updateByAdmin($payload['orderPayment'], $authOperationResult);
            $this->em->flush();

            $this->logData = [];

            // 再決済後の取引ID
            $newPaymentOrderId = $authOperationResult['orderId'];

            // 再決済前の取引を取消
            $payload['orderPayment']->setMemo01($originPaymentOrderId);
            $cancelOperationResult = $this->operateCancel($payload);

            // 再決済後の取引IDを再設定
            $payload['orderPayment']->setMemo01($newPaymentOrderId);
            // memo10更新
            $memo10 = unserialize($payload['orderPayment']->getMemo10());
            $memo10['card_amount'] = floor($payload['order']->getPaymentTotal());
            $payload['orderPayment']->setMemo10(serialize($memo10));

            // キャンセル処理が異常終了の場合
            if (!$cancelOperationResult['isOK']) {
                $this->mdkLogger->info(print_r($cancelOperationResult, true));

                return $cancelOperationResult;
            }

            $this->mdkLogger->info(print_r($authOperationResult, true));

            return $authOperationResult;
        }

        $payId   = $payload['orderPayment']->getMemo03();
        $payName = $this->util->getPayName($payId);

        $this->mdkLogger->info(
            sprintf(
                trans('vt4g_plugin.admin.order.credit.capture.start'),
                $payName
            )
        );

        // レスポンス初期化
        $operationResult = $this->initPaymentResult();
        // 取引ID
        $paymentOrderId = $payload['orderPayment']->getMemo01();
        // memo01から取得できない場合
        if (empty($paymentOrderId)) {
            // 決済申込時のレスポンスから取得できない場合
            if (empty($prevPaymentResult['orderId'])) {
                $this->mdkLogger->fatal(trans('vt4g_plugin.shopping.credit.fatal.order.id'));
                $operationResult['message'] = trans('vt4g_plugin.payment.shopping.error');
                return $operationResult;
            }
            // 決済申込時の結果から取得
            $paymentOrderId = $prevPaymentResult['orderId'];
        }

        $mdkRequest = new \CardCaptureRequestDto();

        // 取引ID
        $mdkRequest->setOrderId($paymentOrderId);
        // 決済金額
        $mdkRequest->setAmount(floor($payload['order']->getPaymentTotal()));

        $mdkTransaction = new \TGMDK_Transaction();
        $mdkResponse = $mdkTransaction->execute($mdkRequest);

        // レスポンス検証
        if (!isset($mdkResponse)) {
            $this->mdkLogger->fatal(trans('vt4g_plugin.payment.shopping.mdk.error'));
            $operationResult['message'] = trans('vt4g_plugin.payment.shopping.error');

            $this->mdkLogger->info(print_r($operationResult, true));

            return $operationResult;
        }

        // 結果コード
        $operationResult['mStatus'] = $mdkResponse->getMStatus();
        // 詳細コード
        $operationResult['vResultCode'] = $mdkResponse->getVResultCode();
        // エラーメッセージ
        $operationResult['mErrMsg'] = $mdkResponse->getMErrMsg();

        // 異常終了レスポンスの場合
        if ($operationResult['mStatus'] === $this->vt4gConst['VT4G_RESPONSE']['MSTATUS']['NG']) {
            $operationResult['message']  = $operationResult['vResultCode'].':';
            $operationResult['message'] .= $operationResult['mErrMsg'];

            $this->mdkLogger->info(print_r($operationResult, true));

            return $operationResult;
        }

        $operationResult['isOK']        = true;
        // 取引ID
        $operationResult['orderId']     = $mdkResponse->getOrderId();
        // 決済サービスタイプ
        $operationResult['payStatus']   = $this->vt4gConst['VT4G_PAY_STATUS']['CAPTURE']['VALUE'];

        // 決済変更ログ情報を設定
        $this->logData = [];
        $this->setLog($payload['order'], $operationResult);

        // 変更後の金額をログ出力
        $amount = number_format(floor($payload['order']->getPaymentTotal()));
        $this->setLogInfo('売上確定金額', $amount);

        $this->mdkLogger->info(trans('vt4g_plugin.shopping.credit.order.id'). $operationResult['orderId']);
        $this->mdkLogger->info(trans('vt4g_plugin.shopping.credit.capture.amount'). $amount);

        // ログの出力
        $this->mdkLogger->info(print_r($operationResult, true));

        return $operationResult;
    }

    /**
     * 決済処理の実行
     *
     * @param  array $payload 決済に使用するデータ
     * @return array          決済結果データ
     */
    public function operateNewly($payload)
    {
        $payId = $payload['orderPayment']->getMemo03();
        $payName = $this->util->getPayName($payId);
        $accountCardId = $payload['inputs']->get('accountCardId');
        $reTradeOrderId = $payload['inputs']->get('reTradeOrderId');
        $jpo = $payload['inputs']->get('jpo');
        $withCapture = $payload['inputs']->get('withCapture');

        $this->mdkLogger->info(
            sprintf(
                trans('vt4g_plugin.admin.order.credit.newly.start'),
                $payName
            )
        );

        // レスポンス初期化
        $operationResult = $this->initPaymentResult();

        if ((empty($accountCardId) && empty($reTradeOrderId)) || (!empty($accountCardId) && !empty($reTradeOrderId))
            || empty($jpo) || !($withCapture === '0' || $withCapture === '1')) {
            $this->mdkLogger->fatal(
                sprintf(
                    trans('vt4g_plugin.admin.order.credit.param.error'),
                    $accountCardId,
                    $reTradeOrderId,
                    $jpo,
                    $withCapture
                    )
                );
            $operationResult['message'] = trans('vt4g_plugin.admin.order.update_payment_status.error');
            return $operationResult;
        }
        $mdkRequest = ($accountCardId) ? new \CardAuthorizeRequestDto() : new \CardReAuthorizeRequestDto();
        $mdkRequest->setOrderId($this->getMdkOrderId($payload['order']->getid()));
        $mdkRequest->setAmount(floor($payload['order']->getPaymentTotal()));
        $mdkRequest->setJpo($jpo);
        $mdkRequest->setWithCapture($withCapture ? 'true' : 'false');

        if ($accountCardId) {
            $mdkRequest->setCardId($accountCardId);
            $mdkRequest->setAccountId($payload['order']->getCustomer()->vt4g_account_id);
        } else {
            $mdkRequest->setOriginalOrderId($reTradeOrderId);
        }

        $mdkTransaction = new \TGMDK_Transaction();
        $mdkResponse = $mdkTransaction->execute($mdkRequest);

        // レスポンス検証
        if (!isset($mdkResponse)) {
            $this->mdkLogger->fatal(trans('vt4g_plugin.payment.shopping.mdk.error'));
            $operationResult['message'] = trans('vt4g_plugin.payment.shopping.error');
            return $operationResult;
        }

        // 結果コード
        $operationResult['mStatus'] = $mdkResponse->getMStatus();
        // 詳細コード
        $operationResult['vResultCode'] = $mdkResponse->getVResultCode();
        // エラーメッセージ
        $operationResult['mErrMsg'] = $mdkResponse->getMErrMsg();

        // 異常終了レスポンスの場合
        if ($operationResult['mStatus'] !== $this->vt4gConst['VT4G_RESPONSE']['MSTATUS']['OK']) {
            $operationResult['message']  = $operationResult['vResultCode'].':';
            $operationResult['message'] .= $operationResult['mErrMsg'];

            return $operationResult;
        }

        // 通常クレジットカード決済の正常終了
        $operationResult['isOK'] = true;
        // 取引ID取得
        $operationResult['orderId'] = $mdkResponse->getOrderId();
        // マスクされたクレジットカード番号
        $operationResult['cardNumber'] = $mdkResponse->getReqCardNumber();
        // 入力したカード名義
        $operationResult['lastName'] = null;
        $operationResult['firstName'] = null;
        $operationResult['paymentType'] = substr($jpo, 0, 2);
        $operationResult['paymentCount'] = substr($jpo, 2);
        // memo10
        $operationResult['card_amount'] = floor($payload['order']->getPaymentTotal());
        $operationResult['card_type'] = $jpo;
        // memo02
        $operationResult['customerId'] = $payload['order']->getCustomer()->getId();

        // 決済状態を保持
        $operationResult['payStatus'] = $withCapture
            ? $this->vt4gConst['VT4G_PAY_STATUS']['CAPTURE']['VALUE']
            : $this->vt4gConst['VT4G_PAY_STATUS']['AUTH']['VALUE'];
        $operationResult['mpiHosting'] = false;
        $operationResult['withCapture'] = $withCapture;

        // 決済変更ログ情報を設定
        $this->logData = [];
        $this->setLog($payload['order'], $operationResult);
        // 変更後の金額をログ出力
        $amount = number_format(floor($payload['order']->getPaymentTotal()));
        $this->setLogInfo('売上確定金額', $amount);

        // ログの出力
        $this->mdkLogger->info(print_r($operationResult, true));

        return $operationResult;
    }

    /**
     * キャンセル処理
     *
     * @param  array $payload キャンセル処理に使用するデータ
     * @return array          キャンセル処理結果データ
     */
    public function operateCancel($payload)
    {
        // キャンセル共通処理
        list($operationResult, $mdkResponse) = parent::operateCancel($payload);

        // ログの出力
        $this->mdkLogger->info(print_r($operationResult, true));

        if ($operationResult['isOK']) {
            $memo10 = unserialize($payload['orderPayment']->getMemo10());
            if ($memo10 !== false && !empty($memo10['card_amount'])) {
                $amount = number_format($memo10['card_amount']);
                $this->setLogInfo('取消金額', $amount);

                $this->mdkLogger->info(trans('vt4g_plugin.shopping.credit.order.id'). $operationResult['orderId']);
                $this->mdkLogger->info(trans('vt4g_plugin.shopping.credit.cancel.amount'). $amount);
            }
        }

        return $operationResult;
    }

    /**
     * 再決済処理
     *
     * @param  array   $payload       再決済処理に使用するデータ
     * @param  integer $paymentStatus 決済ステータス
     * @return array                  再決済処理結果データ
     */
    public function operateAuth($payload, $paymentStatus)
    {
        $paymentId   = $payload['order']->getPayment()->getId();
        $payId       = $payload['orderPayment']->getMemo03();
        $payName     = $this->util->getPayName($payId);
        $paymentInfo = $this->util->getPaymentMethodInfo($paymentId);
        // 決済申込時のレスポンス
        $prevPaymentResult = unserialize($payload['orderPayment']->getMemo05());

        // レスポンス初期化
        $operationResult = $this->initPaymentResult();

        $this->mdkLogger->info(
            sprintf(
                trans('vt4g_plugin.admin.order.credit.again.start'),
                $payName
            )
        );

        // 再決済対象の取引ID
        $paymentOrderId = $payload['orderPayment']->getMemo01();
        // memo01から取得できない場合
        if (empty($paymentOrderId)) {
            // 決済申込時のレスポンスから取得できない場合
            if (empty($prevPaymentResult['orderId'])) {
                $this->mdkLogger->fatal(trans('vt4g_plugin.shopping.credit.fatal.order.id'));
                $operationResult['message'] = trans('vt4g_plugin.payment.shopping.error');
                return $operationResult;
            }
            // 決済申込時の結果から取得
            $paymentOrderId = $prevPaymentResult['orderId'];
        }

        // 支払回数
        $memo10 = unserialize($payload['orderPayment']->getMemo10());
        $cardType = $memo10['card_type'];

        $mdkRequest = new \CardReAuthorizeRequestDto();

        // 取引ID
        $mdkRequest->setOrderId($this->getMdkOrderId($payload['order']->getid()));
        // 再決済対象の取引ID
        $mdkRequest->setOriginalOrderId($paymentOrderId);
        // 決済金額
        $mdkRequest->setAmount(floor($payload['order']->getPaymentTotal()));
        // 支払回数
        $mdkRequest->setJpo($cardType);

        // 売上フラグ
        if ($paymentStatus == $this->vt4gConst['VT4G_PAY_STATUS']['CAPTURE']['VALUE']) {
            $mdkRequest->setWithCapture('true');
        } else {
            $mdkRequest->setWithCapture($paymentInfo['withCapture'] ? 'true' : 'false');
        }

        $mdkTransaction = new \TGMDK_Transaction();
        $mdkResponse = $mdkTransaction->execute($mdkRequest);

        $operationResult['payStatus'] = $paymentStatus;

        // レスポンス検証
        if (!isset($mdkResponse)) {
            $this->mdkLogger->fatal(trans('vt4g_plugin.payment.shopping.mdk.error'));
            $operationResult['message'] = trans('vt4g_plugin.payment.shopping.error');
            return $operationResult;
        }

        // 結果コード
        $operationResult['mStatus'] = $mdkResponse->getMStatus();
        // 詳細コード
        $operationResult['vResultCode'] = $mdkResponse->getVResultCode();
        // エラーメッセージ
        $operationResult['mErrMsg'] = $mdkResponse->getMErrMsg();

        // 異常終了レスポンスの場合
        if ($operationResult['mStatus'] === $this->vt4gConst['VT4G_RESPONSE']['MSTATUS']['NG']) {
            $operationResult['message']  = $operationResult['vResultCode'].':';
            $operationResult['message'] .= $operationResult['mErrMsg'];

            $this->mdkLogger->info(print_r($operationResult, true));

            return $operationResult;
        }

        $operationResult['isOK']        = true;
        // 取引ID
        $operationResult['orderId']     = $mdkResponse->getOrderId();
        // 決済サービスタイプ
        $operationResult['serviceType'] = $mdkResponse->getServiceType();
        // 決済ステータス
        $operationResult['payStatus']   = $paymentStatus;

        // 取引IDを更新
        $orderPayment = $this->em->getRepository(vt4gOrderPayment::class)->find($payload['order']->getId());
        $orderPayment->setMemo01($operationResult['orderId']);
        $this->em->flush();

        // 決済変更ログ情報を設定
        $this->setLog($payload['order'], $operationResult);

        // 変更後の金額をログ出力
        $amount = number_format(floor($payload['order']->getPaymentTotal()));
        $this->setLogInfo('再取引金額', $amount);

        $this->mdkLogger->info(trans('vt4g_plugin.shopping.credit.order.id'). $operationResult['orderId']);
        $this->mdkLogger->info(trans('vt4g_plugin.shopping.credit.again.amount'). $amount);

        $this->mdkLogger->info(print_r($operationResult, true));

        return $operationResult;
    }

    /**
     * クレジットカード決済 再取引用 注文情報を取得
     *
     * @param  integer $customerId 会員ID
     * @return array               注文情報
     */
    public function getReTradeCards($customerId)
    {
        $limitDate = date(
            $this->vt4gConst['VT4G_CREDIT_RETRADE']['DATETIME_FORMAT'],
            strtotime(sprintf('- %d month', $this->vt4gConst['VT4G_CREDIT_RETRADE']['VALID_MONTH']))
        );

        return $this->em->getRepository(Vt4gOrderPayment::class)->getReTradeCards(
            $customerId,
            $this->vt4gConst['VT4G_PAYTYPEID_CREDIT'],
            $limitDate,
            $this->vt4gConst['VT4G_CREDIT_RETRADE']['LIMIT_CARDS']
        );
    }

    /**
     * MDKリクエストを生成
     *
     * @param  array  &$sources リクエスト生成に必要なデータ
     * @return object           MDKリクエストオブジェクト
     */
    private function makeMdkRequest(&$sources)
    {
        if ($sources['isMpi']) {
            $request = $sources['isReTrade']
                ? new \MpiReAuthorizeRequestDto() // 本人認証あり (再取引)
                : new \MpiAuthorizeRequestDto();  // 本人認証あり
        } else {
            $request = $sources['isReTrade']
                ? new \CardReAuthorizeRequestDto() // 本人認証なし (再取引)
                : new \CardAuthorizeRequestDto();  // 本人認証なし
        }

        // 受注番号
        $request->setOrderId($this->getMdkOrderId($sources['order']->getId()));
        $request->setAmount($sources['amount']);

        // 再取引の場合
        if ($sources['isReTrade']) {
            // 元取引ID
            $request->setOriginalOrderId($sources['inputs']->get('payment_order_id'));
            // 支払い方法
            $request->setJpo($sources['inputs']->get('payment_credit_one_click')['payment_type']);
        } else {
            // ベリトランス会員ID決済利用の場合
            if ($sources['useAccountPayment'] && !empty($sources['user']) && $sources['doRegistCardinfo']) {
                $accountId = $this->getAccountId($sources['user']);
                // ベリトランス会員ID情報をログ出力
                $this->mdkLogger->info((empty($sources['user']->vt4g_account_id)
                                        ? trans('vt4g_plugin.shopping.credit.account.payment.new')
                                        : trans('vt4g_plugin.shopping.credit.account.payment')
                                        )
                                        .$accountId);
                // ベリトランス会員ID
                $request->setAccountId($accountId);
                // 仮登録フラグによりMPIのレスポンスは会員情報が空になるので、MPIのときはここの値を使用する
                $sources['accountId'] = $accountId;
            }

            // 登録済みのカードを使用する場合
            if ($sources['mode'] === 'account') {
                $cardId = $sources['inputs']->get('card_id');
                $this->mdkLogger->info(trans('vt4g_plugin.shopping.credit.account.card').$cardId);
                // カードID
                $request->setCardId($cardId);
                // 支払い方法
                $request->setJpo($sources['inputs']->get('payment_credit_account')['payment_type']);
            } else {
                // トークン情報をログ出力
                $this->mdkLogger->info(
                    sprintf(
                        trans('vt4g_plugin.shopping.credit.token'),
                        $sources['inputs']->get('token_id'),
                        $sources['inputs']->get('token_expire_date')
                    )
                );
                // MDKトークン
                $request->setToken($sources['inputs']->get('token_id'));
                // 支払い方法
                $request->setJpo($sources['inputs']->get('payment_credit')['payment_type']);
            }
        }

        // 決済種別
        $request->setWithCapture($sources['paymentInfo']['withCapture'] ? 'true' : 'false');

        // 本人認証の場合
        if ($sources['isMpi']) {
            $request->setServiceOptionType($sources['paymentInfo']['mpi_option']);
            // URL設定
            $redirectionUri = $this->util->generateUrl(
                'vt4g_shopping_payment',
                ['mode' => 'comp']
            );
            $request->setRedirectionUri($redirectionUri);
            $request->setHttpUserAgent($_SERVER['HTTP_USER_AGENT']);
            $request->setHttpAccept($_SERVER['HTTP_ACCEPT']);
            $request->setVerifyTimeout($this->vt4gConst['VT4G_CREDIT_VERIFY_TIMEOUT']);
            // 仮登録フラグの設定
            if ($sources['useAccountPayment'] && !empty($sources['user']) && $sources['mode'] != 'account') {
                $request->setTempRegistration($this->vt4gConst['VT4G_CREDIT_PAYNOWID_TEMP_REG']);
            }
        }

        return $request;
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
    private function handleMdkResponse($response, $sources, &$error)
    {
        // レスポンス初期化
        $this->initPaymentResult();

        // レスポンス検証
        if (!isset($response)) {
            // システムエラー
            $this->mdkLogger->fatal(trans('vt4g_plugin.payment.shopping.mdk.error'));
            $error['payment'] = $this->getErrorMessage();
            return false;
        }

        // 結果コード
        $this->paymentResult['mStatus'] = $response->getMStatus();
        // 詳細コード
        $this->paymentResult['vResultCode'] = $response->getVResultCode();
        // エラーメッセージ
        $this->paymentResult['mErrMsg'] = $response->getMerrMsg();

        // 異常終了レスポンスの場合
        if ($this->paymentResult['mStatus'] === $this->vt4gConst['VT4G_RESPONSE']['MSTATUS']['NG']) {
            $this->mdkLogger->fatal(trans('vt4g_plugin.shopping.credit.fatal.payment'));
            $this->mdkLogger->fatal(print_r($this->paymentResult, true));
            $error['credit'] = $this->getErrorMessage();
            return false;
        }

        // 本人認証なし かつ 保留レスポンスの場合
        if (!$sources['isMpi'] && $this->paymentResult['mStatus'] === $this->vt4gConst['VT4G_RESPONSE']['MSTATUS']['PENDING']) {
            // リトライを実行
            $retryResult = $this->retryMdkRequest($sources['orderId'], $error);
            if (!$retryResult) {
                $error['credit'] = $this->getErrorMessage();
                return false;
            }

            // リトライのレスポンスを使用
            $response = $retryResult;
        }

        // 本人認証ありの場合
        if ($sources['isMpi']) {
            return $sources['isAfterAuth']
                ? $this->handleMpiAfterAuthResponse($response, $sources, $error)
                : $this->handleMpiBeforeAuthResponse($response, $sources, $error);
        }

        // 本人認証なしの場合
        return $this->handleNormalResponse($response, $sources, $error);
    }

    /**
     * MDKリクエストのレスポンスのハンドリング
     * (本人認証なしパターン)
     *
     * @param  object  $response MDKリクエストのレスポンス
     * @param  array   $sources  ハンドリングに必要なデータ
     * @param  array   &$error   エラー表示用配列
     * @return boolean           レスポンスを正常に処理したかどうか
     */
    private function handleNormalResponse($response, $sources, &$error)
    {
        // 通常クレジットカード決済の正常終了
        $this->paymentResult['isOK'] = true;
        // 取引ID取得
        $this->paymentResult['orderId'] = $response->getOrderId();
        // マスクされたクレジットカード番号
        $this->paymentResult['cardNumber'] = $response->getReqCardNumber();
        // 支払い方法・支払い回数
        $jpo = $response->getReqJpoInformation();
        $this->paymentResult['paymentType'] = substr($jpo, 0, 2);
        $this->paymentResult['paymentCount'] = substr($jpo, 2);

        // 決済状態を保持
        $this->paymentResult['payStatus'] = $sources['paymentInfo']['withCapture']
            ? $this->vt4gConst['VT4G_PAY_STATUS']['CAPTURE']['VALUE']
            : $this->vt4gConst['VT4G_PAY_STATUS']['AUTH']['VALUE'];
        $this->paymentResult['mpiHosting'] = false;
        $this->paymentResult['withCapture'] = $sources['paymentInfo']['withCapture'];

        $this->mdkLogger->info(print_r($this->paymentResult, true));

        // 正常終了の場合
        if ($this->paymentResult['isOK']) {
            // ベリトランス会員ID決済の場合
            if ($sources['useAccountPayment'] && !empty($sources['user']) && $sources['doRegistCardinfo']) {
                // ベリトランス会員IDをテーブルに保存
                $accountId = $response->getPayNowIdResponse()->getAccount()->getAccountId();
                $this->saveAccountId($sources['user']->getId(), $accountId);
            }

            $isCompleted = $this->completeCreditOrder($sources['order']);

            // 受注完了処理
            if (!$isCompleted) {
                $this->mdkLogger->fatal(trans('vt4g_plugin.shopping.credit.fatal.complete'));
                $error['payment'] = $this->defaultErrorMessage;
                return false;
            }
        }

        return true;
    }

    /**
     * MDKリクエストのレスポンスのハンドリング
     * (本人認証あり・認証ページへのリダイレクト前処理)
     *
     * @param  object  $response MDKリクエストのレスポンス
     * @param  array   $sources  ハンドリングに必要なデータ
     * @param  array   &$error   エラー表示用配列
     * @return void
     */
    private function handleMpiBeforeAuthResponse($response, $sources, &$error)
    {
        if (!$this->canContinueMpiPayment($sources['paymentInfo'])) {
            $this->mdkLogger->fatal(trans('vt4g_plugin.shopping.credit.fatal.mpi.continue'));
            $error['payment'] = $this->getErrorMessage();
            return false;
        }

        // ベリトランス会員ID決済の場合
        if ($sources['useAccountPayment'] && !empty($sources['user']) && !empty($sources['accountId'])) {
            // ベリトランス会員IDをセッションに保存
            $accountId = $sources['accountId'];
            $this->container->get('session')->set(
                $this->vt4gConst['VT4G_CREDIT_VERITRANS_ID_SESSION_KEY'],
                $accountId
            );
        }

        $this->paymentResult['isOK'] = true;
        $this->paymentResult['orderId'] = $response->getOrderId();
        $this->paymentResult['cardNumber'] = $response->getReqCardNumber();
        $jpo = $response->getReqJpoInformation();
        $this->paymentResult['paymentType'] = substr($jpo, 0, 2);
        $this->paymentResult['paymentCount'] = substr($jpo, 2);
        $this->paymentResult['mpiHosting'] = 1;
        $this->paymentResult['isMpi'] = true;
        $this->paymentResult['withCapture'] = $sources['paymentInfo']['withCapture'];
        $this->paymentResult['resResponseContents'] = $response->getResResponseContents();

        $this->mdkLogger->info(print_r($this->paymentResult, true));

        // 認証ページ用レスポンスを表示して処理を終了
        echo $this->paymentResult['resResponseContents'];
        exit;
    }

    /**
     * MDKリクエストのレスポンスのハンドリング
     * (本人認証あり・認証ページからのリダイレクト後処理)
     *
     * @param  object  $response MDKリクエストのレスポンス
     * @param  array   $sources  ハンドリングに必要なデータ
     * @param  array   &$error   エラー表示用配列
     * @return boolean           レスポンスを正常に処理したかどうか
     */
    private function handleMpiAfterAuthResponse($response, $sources, &$error)
    {
        // オーダー情報リスト
        $orderInfoList   = $response->getOrderInfos()->getOrderInfo();
        // 決済トランザクション
        $transactionInfo = $orderInfoList[0]->getTransactionInfos()->getTransactionInfo();

        // 最新のMPIのレコードを探索
        $lastTransaction = null;
        $lastProperTransaction = null;
        if (is_array($transactionInfo)) {
            $lastMpiTxnId = null;
            foreach ($transactionInfo as $transaction) {
                $txnId = $transaction->getTxnId();
                $properTransaction = $transaction->getProperTransactionInfo();
                $isMpiTxn = $properTransaction->getTxnKind() == 'mpi';
                if ($isMpiTxn && ($txnId > $lastMpiTxnId)) {
                    $lastTransaction = $transaction;
                    $lastProperTransaction = $properTransaction;
                    $lastMpiTxnId = $txnId;
                }
            }
        }

        // 対象が見つからなかった場合
        if (is_null($lastTransaction)) {
            $this->initPaymentResult();
            $this->paymentResult['mErrMsg'] = trans('vt4g_plugin.shopping.credit.mErrMsg.transaction');
            $this->mdkLogger->fatal(trans('vt4g_plugin.shopping.credit.fatal.mpi.payment'));
            $error['payment'] = $this->getErrorMessage();
            return false;
        }

        // 結果コード
        $this->paymentResult['mStatus'] = $lastTransaction->getMStatus();
        // 詳細コード
        $this->paymentResult['vResultCode'] = $lastTransaction->getVResultCode();

        // 本人認証の結果検証
        if (!$this->verifyMpiPayment($sources['paymentInfo'])) {
            $this->paymentResult['mErrMsg'] = trans('vt4g_plugin.shopping.credit.mErrMsg.mpi.payment');
            $this->mdkLogger->fatal(trans('vt4g_plugin.shopping.credit.fatal.mpi.payment'));
            $error['payment'] = $this->getErrorMessage();
            return false;
        }
        // カード与信の結果検証
        if (!$this->verifyCreditPayment()) {
            $this->paymentResult['mErrMsg'] = trans('vt4g_plugin.shopping.credit.mErrMsg.credit.payment');
            $this->mdkLogger->fatal(trans('vt4g_plugin.shopping.credit.fatal.credit.payment'));
            $error['payment'] = $this->getErrorMessage();
            return false;
        }

        $withCapture = $this->getWithCapture($sources['order']);
        // プラグインver.1.0.0で登録した決済が本人認証から戻ってきた場合は
        // 決済情報に処理区分が無いので、代わりに設定画面の値を使用する
        if (is_null($withCapture)) {
            $withCapture = $sources['paymentInfo']['withCapture'];
        }

        // 正常終了
        $this->paymentResult['isOK'] = true;
        $this->paymentResult['mErrMsg'] = '';
        $this->paymentResult['orderId'] = $orderInfoList[0]->getOrderid();
        $jpo = $lastProperTransaction->getReqJpoInformation();
        $this->paymentResult['paymentType'] = substr($jpo, 0, 2);
        $this->paymentResult['paymentCount'] = substr($jpo, 2);
        $this->paymentResult['payStatus'] = $withCapture
            ? $this->vt4gConst['VT4G_PAY_STATUS']['CAPTURE']['VALUE']
            : $this->vt4gConst['VT4G_PAY_STATUS']['AUTH']['VALUE'];
        $this->paymentResult['mpiHosting'] = true;
        $this->paymentResult['withCapture'] = $withCapture;

        $this->mdkLogger->info(print_r($this->paymentResult, true));

        // 正常終了の場合
        if ($this->paymentResult['isOK']) {
            // ベリトランス会員ID決済の場合
            if ($sources['useAccountPayment']) {
                $session = $this->container->get('session');
                $sessionKey = $this->vt4gConst['VT4G_CREDIT_VERITRANS_ID_SESSION_KEY'];
                // セッションにベリトランス会員IDが保存されている場合
                if ($session->has($sessionKey)) {
                    // ベリトランス会員IDをテーブルに保存
                    $this->saveAccountId($sources['user']->getId(), $session->get($sessionKey));
                    // セッションから削除
                    $session->remove($sessionKey);
                }
            }

            $isCompleted = $this->completeCreditOrder($sources['order']);

            // 受注完了処理
            if (!$isCompleted) {
                $this->mdkLogger->fatal(trans('vt4g_plugin.shopping.credit.fatal.complete'));
                $error['payment'] = $this->getErrorMessage();
                return false;
            }
        }

        return true;
    }

    /**
     * MDKリクエストのリトライを実行
     *
     * @param  string      $orderId 取引ID
     * @param  array       &$error  画面表示用エラー配列
     * @return object|null          リトライレスポンス(異常終了の場合にnull)
     */
    private function retryMdkRequest($orderId, &$error)
    {
        $isSucceeeded = false;
        for ($count = 0; $count < $this->vt4gConst['VT4G_REQUEST']['CREDIT']['RETRY_LIMIT']; $count++) {
            // インターバル
            sleep($this->vt4gConst['VT4G_REQUEST']['CREDIT']['RETRY_WAIT']);

            $this->mdkLogger->info(trans('vt4g_plugin.payment.shopping.mdk.restart'));

            $mdkRequest = new \CardRetryRequestDto();
            $mdkRequest->setOrderId($orderId);
            $mdkTransaction = new \TGMDK_Transaction();
            $mdkResponse = $mdkTransaction->execute($mdkRequest);

            // 異常終了の場合
            if (!isset($mdkResponse)) {
                $this->mdkLogger->fatal(trans('vt4g_plugin.shopping.credit.fatal.restart'));
                $error['payment'] = $this->getErrorMessage();
                return null;
            }

            $this->paymentResult['mStatus']     = $mdkResponse->getMStatus();
            $this->paymentResult['vResultCode'] = $mdkResponse->getVResultCode();
            $this->paymentResult['mErrMsg']     = $mdkResponse->getMerrMsg();

            // 正常終了の場合
            if ($mdkResponse->getMStatus() === $this->vt4gConst['VT4G_RESPONSE']['MSTATUS']['OK']) {
                $isSucceeeded = true;
                break;
            }
        }

        // リトライが全部失敗した場合
        if (!$isSucceeeded) {
            $this->mdkLogger->fatal(trans('vt4g_plugin.shopping.credit.fatal.restart.end'));
            $error['payment'] = $this->getErrorMessage();
            return null;
        }

        return $mdkResponse;
    }

    /**
     * ベリトランス会員IDを会員テーブルに保存
     *
     * @param  integer $customerId 加盟店会員ID
     * @param  string  $accountId  ベリトランス会員ID
     * @return void
     */
    private function saveAccountId($customerId, $accountId)
    {
        $customer = $this->em->getRepository(Customer::class)->find($customerId);

        // ベリトランス会員IDが未設定の場合に保存
        if (empty($customer->vt4g_account_id)) {
            $customer->vt4g_account_id = $accountId;
            $this->em->flush();
        }
    }

    /**
     * 受注完了処理
     *
     * @param  array $order 注文データ
     * @return void
     */
    public function completeCreditOrder($order)
    {
        if (!$this->paymentResult['isOK']) {
            return false;
        }

        // 決済情報 (memo05)
        $payment = $this->paymentResult;

        // メール情報 (memo06)
        $this->mailData = [];
        $this->setMailTitle($this->vt4gConst['VT4G_PAYNAME_PAYTYPEID_10']);
        $this->setMailInfo('決済取引ID', $this->paymentResult['orderId']);
        $paymentMethod = $this->util->getPaymentMethod($order->getPayment()->getId());
        $this->setMailAdminSetting($paymentMethod);

        // 決済変更ログ情報 (plg_vt4g_order_logテーブル)
        $this->setLog($order);

        // 受注ステータス更新
        if (!$this->setNewOrderStatus($order,$payment['withCapture'])) {
            return false;
        }

        // 受注完了処理
        $this->completeOrder($order, $payment, $this->logData, $this->mailData);

        return true;
    }

    /**
     * 再取引決済 元取引IDのバリデーション
     *
     * @param  integer $paymentOrderId 元取引ID
     * @param  integer $customerId     会員ID
     * @return boolean                 元取引が存在するか
     */
    private function isValidReTradeOrder($paymentOrderId, $customerId)
    {
        $limitDate = date(
            $this->vt4gConst['VT4G_CREDIT_RETRADE']['DATETIME_FORMAT'],
            strtotime(sprintf('- %d month', $this->vt4gConst['VT4G_CREDIT_RETRADE']['VALID_MONTH']))
        );

        return $this->em->getRepository(Vt4gOrderPayment::class)->existsReTradeOrder(
            $customerId,
            $this->vt4gConst['VT4G_PAYTYPEID_CREDIT'],
            $limitDate,
            $paymentOrderId
        );
    }

    /**
     * 本人認証の通信結果から決済の可否を判定
     *
     * @param  array   $paymentInfo クレジットカード決済の支払設定
     * @return boolean              決済の可否
     */
    private function canContinueMpiPayment($paymentInfo)
    {
        $mpiOption = $paymentInfo['mpi_option'];

        // 結果詳細コードの先頭4桁を取得
        $code = substr($this->paymentResult['vResultCode'], 0, 4);

        switch ($mpiOption) {
            case $this->vt4gConst['VT4G_CREDIT_MPI_OPTION']['COMPLETE']: // 完全認証
                switch ($code) {
                    case 'G001':
                        return true;
                    default:
                        return false;
                }
            case $this->vt4gConst['VT4G_CREDIT_MPI_OPTION']['COMPANY']: // 通常認証 カード会社リスク
                switch ($code) {
                    case 'G001':
                        // 本人認証可
                        return true;
                    case 'G002':
                    case 'G003':
                        // カード会社リスク負担により決済処理に移行
                        return true;
                    default:
                        return false;
                }
            case $this->vt4gConst['VT4G_CREDIT_MPI_OPTION']['MERCHANT']: // 通常認証 加盟店リスク
                switch ($code) {
                    case 'G001':
                        // 本人認証可
                        return true;
                    case 'G002':
                    case 'G003':
                    case 'G004':
                    case 'G005':
                    case 'G006':
                        // カード会社あるいは加盟店側のリスク負担により決済処理に移行
                        return true;
                    default:
                        return false;
                }
            default:
                return false;
        }

        return false;
    }

    /**
     * 本人認証の検証結果から決済の可否を判定
     *
     * @param  array   $paymentInfo クレジットカード決済の支払設定
     * @return boolean              決済の可否
     */
    private function verifyMpiPayment($paymentInfo)
    {
        $mpiOption = $paymentInfo['mpi_option'];

        // 結果詳細コードの先頭4桁を取得
        $code = substr($this->paymentResult['vResultCode'], 0, 4);

        switch ($mpiOption) {
            case $this->vt4gConst['VT4G_CREDIT_MPI_OPTION']['COMPLETE']: // 完全認証
                switch ($code) {
                    case 'G011':
                        return true;
                    default:
                        return false;
                }
            case $this->vt4gConst['VT4G_CREDIT_MPI_OPTION']['COMPANY']: // 通常認証 カード会社リスク
                switch ($code) {
                    case 'G011':
                        // 本人認証可
                        return true;
                    case 'G012':
                    case 'G002':
                    case 'G003':
                        // 認証は成功なのでカード取引結果を使用
                        return true;
                    default:
                        return false;
                }
            case $this->vt4gConst['VT4G_CREDIT_MPI_OPTION']['MERCHANT']: // 通常認証 加盟店リスク
                switch ($code) {
                    case 'G011':
                        // 本人認証可
                        return true;
                    case 'G012':
                    case 'G013':
                    case 'G014':
                    case 'G002':
                    case 'G003':
                    case 'G004':
                    case 'G005':
                    case 'G006':
                        // 認証は成功なのでカード取引結果を使用
                        return true;
                    default:
                        return false;
                }
            default:
                return false;
        }
    }

    /**
     * カード与信の検証結果から決済の可否判定
     *
     * @return boolean 決済の可否
     */
    private function verifyCreditPayment()
    {
        // 結果詳細コード 第2ブロックの4桁を取得
        $code = substr($this->paymentResult['vResultCode'], 4, 4);

        switch ($code) {
            case 'A001': // 成功
            case 'A003': // 複製の成功
                return true;
            default:
                return false;
        }

        return false;
    }

    /**
     * ベリトランス会員IDを取得
     * (登録されていない場合は新しく採番)
     *
     * @param  object $customer 会員情報
     * @return string           ベリトランス会員ID
     */
    private function getAccountId($customer)
    {
        return empty($customer->vt4g_account_id)
            ? $this->generateAccountId($customer)
            : $customer->vt4g_account_id;
    }

    /**
     * ベリトランスIDを新規採番
     *
     * @param  object $customer 会員情報
     * @return string           ベリトランス会員ID
     */
    private function generateAccountId($customer)
    {
        $paymentInfo = $this->util->getPaymentMethodInfoByPayId($this->vt4gConst['VT4G_PAYTYPEID_CREDIT']);
        $prefix     = $paymentInfo['veritrans_id_prefix'];
        $customerId = $this->util->zeroPadding($customer->getId(), $this->vt4gConst['VT4G_CUSTOMER_ID_DIGITS']);
        $hash       = md5($customer->getEmail());
        $now        = date("YmdHis");

        return "{$prefix}{$customerId}@{$hash}{$now}";
    }

    /**
     * メール内容の設定と完了画面の内容設定
     *
     * @param  object $order         注文データ
     * @param  array  $paymentResult 決済レスポンスデータ
     * @return array  $mailData      メールの説明文
     */
    public function setMail($order, $paymentResult = null)
    {
        if (is_null($paymentResult)) {
            $paymentResult = $this->paymentResult;
        }

        $this->mailData = [];
        $this->setMailTitle($this->vt4gConst['VT4G_PAYNAME_PAYTYPEID_10']);
        $this->setMailInfo('決済取引ID', $paymentResult['orderId']);
        $paymentMethod = $this->util->getPaymentMethod($order->getPayment()->getId());
        $this->setMailAdminSetting($paymentMethod);

        return $this->mailData;
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
        $payStatusName = $this->util->getPaymentStatusName($paymentResult['payStatus']);

        $this->setLogInfo('決済取引ID', $paymentResult['orderId']);
        $this->setLogInfo($payName, sprintf(
            $this->isPaymentRecv ? trans('vt4g_plugin.shopping.credit.recv.status') : trans('vt4g_plugin.shopping.credit.payment.status'),
            $payStatusName
        ));
    }

    /**
     * レスポンスを元にエラーメッセージを取得
     *
     * @return string エラーメッセージ
     */
    private function getErrorMessage()
    {
        $defaultMessage = $this->defaultErrorMessage;
        if (!empty($this->paymentResult['vResultCode'])) {
            $defaultMessage .= '[' . $this->paymentResult['vResultCode'] . ']';
        }

        $vResultCode = $this->paymentResult['vResultCode'];
        if (empty($vResultCode)) {
            return $defaultMessage;
        }

        $extendMessage = '';
        foreach (str_split($vResultCode, 4) as $code) {
            if ($code === '0000') {
                continue;
            }

            $transKey = $this->vt4gConst['VT4G_CREDIT_ERR_TRANS_PREFIX'] . $code;
            $transMessage = trans($transKey);
            if ($transMessage !== $transKey) {
                $extendMessage .= '<br />' . $transMessage;
            }
        }

        if (empty($extendMessage)) {
            $extendMessage = '<br />' . trans($this->vt4gConst['VT4G_CREDIT_ERR_TRANS_PREFIX'].'DEFAULT');
        }

        return $defaultMessage . $extendMessage;
    }

    /**
     * 取引IDを使って本人認証の取引情報を取得します。
     * @param string $payment_order_id 取引ID
     * @return mixed レスポンスDTO
     */
    public function getOrderMpiSearch($payment_order_id)
    {
        $objRequest = new \SearchRequestDto();

        $objRequest->setServiceTypeCd(['mpi']);

        $objRequest->setContainDummyFlag($this->isDummyMode());
        $objRequest->setMaxCount(1);

        $commonParam = new \CommonSearchParameter();
        $commonParam->setOrderId($payment_order_id);

        $searchParameters = new \SearchParameters();
        $searchParameters->setCommon($commonParam);

        $objRequest->setSearchParameters($searchParameters);

        $objTransaction = new \TGMDK_Transaction();
        $objResponse = $objTransaction->execute($objRequest);

        return $objResponse;
    }

    /**
     * ベリトランス問い合わせ結果からカード番号を取得します。
     * @param mixed $result レスポンスDTO
     * @return string $reqCardNo カード番号
     */
    public function getResponseOrderCard($result)
    {
        $reqCardNo = "";

        if ($result->getMstatus() == $this->vt4gConst['VT4G_RESPONSE']['MSTATUS']['OK']) {
            // 成功
            $objOrderInfos = $result->getOrderInfos();
            $arrOrderInfo  = $objOrderInfos->getOrderInfo();
            $objTranInfos = $arrOrderInfo[0]->getTransactionInfos();
            $arrTranInfo  = $objTranInfos->getTransactionInfo();


            // 最新のMPIのレコードを探索
            $objLastTran = null;
            $objLastProperTran = null;
            if (is_array($arrTranInfo) == true) {
                $lastMpiTxn = null;
                foreach ($arrTranInfo as $objTran) {
                    $txnId = $objTran->getTxnId();
                    $objProperTran = $objTran->getProperTransactionInfo();
                    if ($objProperTran->getTxnKind() == 'mpi' && $txnId > $lastMpiTxn) {
                        $objLastTran = $objTran;
                        $objLastProperTran = $objProperTran;
                        $lastMpiTxn = $txnId;
                    }
                }
            }
            if ($objLastTran == null) {
                // 対象が見つからない
                $reqCardNo = "";
                return $reqCardNo; // break;
            }

            // マスクされたカード番号返却
            $reqCardNo = $objLastProperTran->getReqCardNumber();

            // 取得カード番号をMDKログに出力
            $logger = \TGMDK_Logger::getInstance();
            $logger->info(print_r(trans('vt4g_plugin.shopping.credit.order.card'). $reqCardNo, true));

        } else {
            // 失敗
            $reqCardNo = "";
        }

        return $reqCardNo;
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
