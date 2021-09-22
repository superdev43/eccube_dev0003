<?php
/**
 * 決済サービスタイプ：3Dセキュアカード連携、コマンド名：申込の要求Dtoクラス
 *
 * @author Veritrans, Inc.
 *
 */
class MpiAuthorizeRequestDto extends AbstractPaymentCreditRequestDto
{

    /**
     * 決済サービスタイプ<br>
     * 半角英数字<br>
     * 必須項目、固定値<br>
     */
    private $SERVICE_TYPE = "mpi";

    /**
     * 決済サービスコマンド<br>
     * 半角英数字<br>
     * 必須項目、固定値<br>
     */
    private $SERVICE_COMMAND = "Authorize";

    /**
     * 決済サービスオプション<br>
     * 半角英数字<br>
     * 100 文字以内<br>
     * 決済サービスオプションの区分を指定します。<br>
     * 必須項目<br>
     * "mpi-none"：　MPI単体サービス<br>
     * "mpi-complete"：　完全認証<br>
     * "mpi-company"：　通常認証（カード会社リスク負担）<br>
     * "mpi-merchant"：　通常認証（カード会社、加盟店リスク負担）<br>
     */
    private $serviceOptionType;

    /**
     * 取引ID<br>
     * 半角英数字<br>
     * 100 文字以内<br>
     * マーチャント側で取引を一意に表す注文管理IDを指定します。<br>
     * 申込処理ごとに一意である必要があります。<br>
     * 半角英数字、“-”（ハイフン）、“_”（アンダースコア）も使用可能です。<br>
     */
    private $orderId;

    /**
     * 決済金額<br>
     * 半角数字<br>
     * 8 桁以内<br>
     * 決済金額を指定します。<br>
     * 1 以上かつ 99999999 以下である必要があります。<br>
     */
    private $amount;

    /**
     * カード番号<br>
     * 半角英数字、ハイフン、ブランク、ピリオド<br>
     * 26 文字以内<br>
     * クレジットカード番号を指定します。<br>
     * 例） クレジットカード番号は19桁まで処理が可能。<br>
     * （ハイフンを含んでも含まなくても同様に処理が可能）<br>
     * 戻り値としては､上2桁/下4桁の計6桁が返ります。<br>
     */
    private $cardNumber;

    /**
     * カード有効期限<br>
     * 半角数字、スラッシュ<br>
     * 5 文字以内<br>
     * クレジットカードの有効期限を指定します。<br>
     * MM/YY （月 + "/" + 年）の形式<br>
     * 例） "11/09"<br>
     */
    private $cardExpire;

    /**
     * カード接続センター<br>
     * 半角英数字<br>
     * 10 文字以内<br>
     * カード接続センターを指定します。（任意指定）<br>
     * "sln"： Sln接続<br>
     * "jcn"： Jcn接続<br>
     * ※ 指定が無い場合は、デフォルトの接続センターを検証<br>
     */
    private $cardCenter;

    /**
     * 仕向け先コード<br>
     * 半角数字<br>
     * 2 桁固定<br>
     * 仕向け先カード会社コードを指定します。<br>
     * （店舗が加盟店契約をしているカード会社）<br>
     * ※ 最終的に決済を行うカード発行会社ではなく、決済要求電文が最初に仕向けられる加盟店管理会社となります。<br>
     * 01 シティカードジャパン株式会社（ダイナースカード）<br>
     * 02 株式会社 ジェーシービー<br>
     * 03 三菱UFJニコス株式会社（旧DCカード）<br>
     * 04 三井住友カード株式会社（りそなカード株式会社などVISAジャパングループ）<br>
     * 05 三菱UFJニコス株式会社（旧UFJカード）<br>
     * 06 ユーシーカード株式会社<br>
     * 07 アメリカン・<br>
     */
    private $acquirerCode;

    /**
     * JPO<br>
     * 半角英数字<br>
     * 138 文字以内<br>
     * JPOを指定します。（任意指定）<br>
     * "10"<br>
     * "21"+"支払詳細"<br>
     * "22"+"支払詳細"<br>
     * "23"+"支払詳細"<br>
     * "24"+"支払詳細"<br>
     * "25"+"支払詳細"<br>
     * "31"+"支払詳細"<br>
     * "32"+"支払詳細"<br>
     * "33"+"支払詳細"<br>
     * "34"+"支払詳細"<br>
     * "61"+"支払詳細"<br>
     * "62"+"支払詳細"<br>
     * "63"+"支払詳細"<br>
     * "69"+"支払詳細"<br>
     * ※ 指定が無い場合は、デフォルトのJPO（一括払い：パターン"10"）<br>
     */
    private $jpo;

    /**
     * 売上フラグ<br>
     * 半角英数字<br>
     * 5 文字以内<br>
     * 売上フラグを指定します。（任意指定）<br>
     * "true"： 与信・売上<br>
     * "false"： 与信のみ<br>
     * ※ 指定が無い場合は、デフォルトの売上フラグ（与信のみ）<br>
     */
    private $withCapture;

    /**
     * 売上日<br>
     * 半角数字<br>
     * 8 桁固定<br>
     * 売上日を指定します。（任意指定）<br>
     * YYYYMMDD の形式<br>
     * 例） "20090905"<br>
     * ※ 指定が無い場合は、売上日（取引日:与信のとき無視）<br>
     */
    private $salesDay;

    /**
     * 商品コード<br>
     * 半角数字<br>
     * 7 桁以内<br>
     * 商品コードを指定します。（任意指定）<br>
     * ※ 指定が無い場合は、デフォルトの商品コード<br>
     */
    private $itemCode;

    /**
     * セキュリティコード<br>
     * 半角数字<br>
     * 4 桁以内<br>
     * セキュリティコードを指定します。<br>
     */
    private $securityCode;

    /**
     * 誕生日<br>
     * 半角数字<br>
     * 4 桁以内<br>
     * 誕生日 を指定します。<br>
     * カード接続センターがjcnと設定しているときは利用できません。<br>
     */
    private $birthday;

    /**
     * 電話番号<br>
     * 半角数字<br>
     * 4 桁以内<br>
     * 電話番号 を指定します。<br>
     * カード接続センターがjcnと設定しているときは利用できません。<br>
     */
    private $tel;

    /**
     * 名前（名）カナ<br>
     * 半角カナ（ｱ～ﾝ）、半濁点<br>
     * 15バイト以内<br>
     * 名前（名）カナ を指定します。<br>
     * カード接続センターがjcnと設定しているときは利用できません。<br>
     */
    private $firstKanaName;

    /**
     * 名前（姓）カナ<br>
     * 半角カナ（ｱ～ﾝ）、半濁点<br>
     * 15バイト以内<br>
     * 名前（姓）カナ を指定します。<br>
     * カード接続センターがjcnと設定しているときは利用できません。<br>
     */
    private $lastKanaName;

    /**
     * 通貨単位<br>
     * 英字3桁<br>
     */
    private $currencyUnit;

    /**
     * リダイレクションURI<br>
     * 英字記号1024バイト以内<br>
     * 検証結果を返すURIを指定します。指定がない場合には予め登録されたURIを用います。<br>
     */
    private $redirectionUri;

    /**
     * HTTPユーザエージェント<br>
     * 英数字記号無制限<br>
     * コンシューマのブラウザ情報でアプリケーションサーバから取得して設定します。<br>
     */
    private $httpUserAgent;

    /**
     * HTTPアセプト<br>
     * 英数字記号無制限<br>
     * コンシューマのブラウザ情報でアプリケーションサーバから取得して設定します。<br>
     */
    private $httpAccept;

    /**
     * 初回請求年月<br>
     * 半角数字<br>
     * 4 文字固定<br>
     * 初回請求年月を指定します。<br>
     * YYMM （年月）の形式<br>
     * 例） "1310"<br>
     */
    private $firstPayment;

    /**
     * ボーナス初回年月<br>
     * 半角数字<br>
     * 4 文字固定<br>
     * ボーナス初回年月を指定します。<br>
     * YYMM （年月）の形式<br>
     * 例） "1312"<br>
     */
    private $bonusFirstPayment;

    /**
     * 決済金額（多通貨）<br>
     * 半角数字＋小数点<br>
     * 半角数字は 8 桁以内<br>
     * 決済金額（多通貨）を指定します。<br>
     * 0 より大きくかつ 99999999 以下である必要があります。<br>
     */
    private $mcAmount;

    /**
     * プッシュURL<br>
     * URL<br>
     * 最大桁数：256<br>
     * プッシュURLを指定します。<br>
     * ※ 未指定の場合は、マーチャント登録時に設定した値を使用<br>
     */
    private $pushUrl;

    /**
     * 端末種別<br>
     * 半角数字<br>
     * 最大桁数：1<br>
     * "0": PC<br>
     * "1": mobile<br>
     * ※ 未指定の場合は、0:PC。<br>
     */
    private $browserDeviceCategory;

    /**
     * 仮登録フラグ<br>
     * 半角数字<br>
     * 最大桁数：1<br>
     * "0": 仮登録しない<br>
     * "1": 仮登録する<br>
     */
    private $tempRegistration;

    /**
     * 本人認証有効期限<br>
     * 半角数字<br>
     * 最大桁数:3<br>
     * 消費者が本人認証処理を完了するまでの有効期限（分単位）を指定します。（任意指定）<br>
     * 1 以上かつ 999 以下である必要があります。<br>
     * ※ 未指定の場合は、有効期限のチェックを行いません。<br>
     */
    private $verifyTimeout;

    /**
     * 不正検知評価取引情報<br>
     */
    private $fraudDetectionRequest;

    /**
     * 不正検知実施フラグ<br>
     * 半角英数字<br>
     * 5 文字以内<br>
     * 不正検知実施フラグを指定します。（任意指定）<br>
     * "true"： 実施する<br>
     * "false"： 実施しない<br>
     */
    private $withFraudDetection;

    /**
     * ログ用文字列(マスク済み)<br>
     * 半角英数字<br>
     */
    private  $maskedLog;

    /**
     * 決済サービスタイプを取得する<br>
     * @return string 決済サービスタイプ<br>
     */
    public function getServiceType() {
        return $this->SERVICE_TYPE;
    }

    /**
     * 決済サービスコマンドを取得する<br>
     * @return string 決済サービスコマンド<br>
     */
    public function getServiceCommand() {
        return $this->SERVICE_COMMAND;
    }

    /**
     * 決済サービスオプションを取得する<br>
     * @return string 決済サービスオプション<br>
     */
    public function getServiceOptionType() {
        return $this->serviceOptionType;
    }

    /**
     * 決済サービスオプションを設定する<br>
     * @param string $serviceOptionType 決済サービスオプション<br>
     */
    public function setServiceOptionType($serviceOptionType) {
        $this->serviceOptionType = $serviceOptionType;
    }

    /**
     * 取引IDを取得する<br>
     * @return string 取引ID<br>
     */
    public function getOrderId() {
        return $this->orderId;
    }

    /**
     * 取引IDを設定する<br>
     * @param string $orderId 取引ID<br>
     */
    public function setOrderId($orderId) {
        $this->orderId = $orderId;
    }

    /**
     * 決済金額を取得する<br>
     * @return string 決済金額<br>
     */
    public function getAmount() {
        return $this->amount;
    }

    /**
     * 決済金額を設定する<br>
     * @param string $amount 決済金額<br>
     */
    public function setAmount($amount) {
        $this->amount = $amount;
    }

    /**
     * カード番号を取得する<br>
     * @return string カード番号<br>
     */
    public function getCardNumber() {
        return $this->cardNumber;
    }

    /**
     * カード番号を設定する<br>
     * @param string $cardNumber カード番号<br>
     */
    public function setCardNumber($cardNumber) {
        $this->cardNumber = $cardNumber;
    }

    /**
     * カード有効期限を取得する<br>
     * @return string カード有効期限<br>
     */
    public function getCardExpire() {
        return $this->cardExpire;
    }

    /**
     * カード有効期限を設定する<br>
     * @param string $cardExpire カード有効期限<br>
     */
    public function setCardExpire($cardExpire) {
        $this->cardExpire = $cardExpire;
    }

    /**
     * カード接続センターを取得する<br>
     * @return string カード接続センター<br>
     */
    public function getCardCenter() {
        return $this->cardCenter;
    }

    /**
     * カード接続センターを設定する<br>
     * @param string $cardCenter カード接続センター<br>
     */
    public function setCardCenter($cardCenter) {
        $this->cardCenter = $cardCenter;
    }

    /**
     * 仕向け先コードを取得する<br>
     * @return string 仕向け先コード<br>
     */
    public function getAcquirerCode() {
        return $this->acquirerCode;
    }

    /**
     * 仕向け先コードを設定する<br>
     * @param string $acquirerCode 仕向け先コード<br>
     */
    public function setAcquirerCode($acquirerCode) {
        $this->acquirerCode = $acquirerCode;
    }

    /**
     * JPOを取得する<br>
     * @return string JPO<br>
     */
    public function getJpo() {
        return $this->jpo;
    }

    /**
     * JPOを設定する<br>
     * @param string $jpo JPO<br>
     */
    public function setJpo($jpo) {
        $this->jpo = $jpo;
    }

    /**
     * 売上フラグを取得する<br>
     * @return string 売上フラグ<br>
     */
    public function getWithCapture() {
        return $this->withCapture;
    }

    /**
     * 売上フラグを設定する<br>
     * @param string $withCapture 売上フラグ<br>
     */
    public function setWithCapture($withCapture) {
        $this->withCapture = $withCapture;
    }

    /**
     * 売上日を取得する<br>
     * @return string 売上日<br>
     */
    public function getSalesDay() {
        return $this->salesDay;
    }

    /**
     * 売上日を設定する<br>
     * @param string $salesDay 売上日<br>
     */
    public function setSalesDay($salesDay) {
        $this->salesDay = $salesDay;
    }

    /**
     * 商品コードを取得する<br>
     * @return string 商品コード<br>
     */
    public function getItemCode() {
        return $this->itemCode;
    }

    /**
     * 商品コードを設定する<br>
     * @param string $itemCode 商品コード<br>
     */
    public function setItemCode($itemCode) {
        $this->itemCode = $itemCode;
    }

    /**
     * セキュリティコードを取得する<br>
     * @return string セキュリティコード<br>
     */
    public function getSecurityCode() {
        return $this->securityCode;
    }

    /**
     * セキュリティコードを設定する<br>
     * @param string $securityCode セキュリティコード<br>
     */
    public function setSecurityCode($securityCode) {
        $this->securityCode = $securityCode;
    }

    /**
     * 誕生日を取得する<br>
     * @return string 誕生日<br>
     */
    public function getBirthday() {
        return $this->birthday;
    }

    /**
     * 誕生日を設定する<br>
     * @param string $birthday 誕生日<br>
     */
    public function setBirthday($birthday) {
        $this->birthday = $birthday;
    }

    /**
     * 電話番号を取得する<br>
     * @return string 電話番号<br>
     */
    public function getTel() {
        return $this->tel;
    }

    /**
     * 電話番号を設定する<br>
     * @param string $tel 電話番号<br>
     */
    public function setTel($tel) {
        $this->tel = $tel;
    }

    /**
     * 名前（名）カナを取得する<br>
     * @return string 名前（名）カナ<br>
     */
    public function getFirstKanaName() {
        return $this->firstKanaName;
    }

    /**
     * 名前（名）カナを設定する<br>
     * @param string $firstKanaName 名前（名）カナ<br>
     */
    public function setFirstKanaName($firstKanaName) {
        $this->firstKanaName = $firstKanaName;
    }

    /**
     * 名前（姓）カナを取得する<br>
     * @return string 名前（姓）カナ<br>
     */
    public function getLastKanaName() {
        return $this->lastKanaName;
    }

    /**
     * 名前（姓）カナを設定する<br>
     * @param string $lastKanaName 名前（姓）カナ<br>
     */
    public function setLastKanaName($lastKanaName) {
        $this->lastKanaName = $lastKanaName;
    }

    /**
     * 通貨単位を取得する<br>
     * @return string 通貨単位<br>
     */
    public function getCurrencyUnit() {
        return $this->currencyUnit;
    }

    /**
     * 通貨単位を設定する<br>
     * @param string $currencyUnit 通貨単位<br>
     */
    public function setCurrencyUnit($currencyUnit) {
        $this->currencyUnit = $currencyUnit;
    }

    /**
     * リダイレクションURIを取得する<br>
     * @return string リダイレクションURI<br>
     */
    public function getRedirectionUri() {
        return $this->redirectionUri;
    }

    /**
     * リダイレクションURIを設定する<br>
     * @param string $redirectionUri リダイレクションURI<br>
     */
    public function setRedirectionUri($redirectionUri) {
        $this->redirectionUri = $redirectionUri;
    }

    /**
     * HTTPユーザエージェントを取得する<br>
     * @return string HTTPユーザエージェント<br>
     */
    public function getHttpUserAgent() {
        return $this->httpUserAgent;
    }

    /**
     * HTTPユーザエージェントを設定する<br>
     * @param string $httpUserAgent HTTPユーザエージェント<br>
     */
    public function setHttpUserAgent($httpUserAgent) {
        $this->httpUserAgent = $httpUserAgent;
    }

    /**
     * HTTPアセプトを取得する<br>
     * @return string HTTPアセプト<br>
     */
    public function getHttpAccept() {
        return $this->httpAccept;
    }

    /**
     * HTTPアセプトを設定する<br>
     * @param string $httpAccept HTTPアセプト<br>
     */
    public function setHttpAccept($httpAccept) {
        $this->httpAccept = $httpAccept;
    }

    /**
     * 初回請求年月を取得する<br>
     * @return string 初回請求年月<br>
     */
    public function getFirstPayment() {
        return $this->firstPayment;
    }

    /**
     * 初回請求年月を設定する<br>
     * @param string $firstPayment 初回請求年月<br>
     */
    public function setFirstPayment($firstPayment) {
        $this->firstPayment = $firstPayment;
    }

    /**
     * ボーナス初回年月を取得する<br>
     * @return string ボーナス初回年月<br>
     */
    public function getBonusFirstPayment() {
        return $this->bonusFirstPayment;
    }

    /**
     * ボーナス初回年月を設定する<br>
     * @param string $bonusFirstPayment ボーナス初回年月<br>
     */
    public function setBonusFirstPayment($bonusFirstPayment) {
        $this->bonusFirstPayment = $bonusFirstPayment;
    }

    /**
     * 決済金額（多通貨）を取得する<br>
     * @return string 決済金額（多通貨）<br>
     */
    public function getMcAmount() {
        return $this->mcAmount;
    }

    /**
     * 決済金額（多通貨）を設定する<br>
     * @param string $mcAmount 決済金額（多通貨）<br>
     */
    public function setMcAmount($mcAmount) {
        $this->mcAmount = $mcAmount;
    }

    /**
     * プッシュURLを取得する<br>
     * @return string プッシュURL<br>
     */
    public function getPushUrl() {
        return $this->pushUrl;
    }

    /**
     * プッシュURLを設定する<br>
     * @param string $pushUrl プッシュURL<br>
     */
    public function setPushUrl($pushUrl) {
        $this->pushUrl = $pushUrl;
    }

    /**
     * 端末種別を取得する<br>
     * @return string 端末種別<br>
     */
    public function getBrowserDeviceCategory() {
        return $this->browserDeviceCategory;
    }

    /**
     * 端末種別を設定する<br>
     * @param string $browserDeviceCategory 端末種別<br>
     */
    public function setBrowserDeviceCategory($browserDeviceCategory) {
        $this->browserDeviceCategory = $browserDeviceCategory;
    }

    /**
     * 仮登録フラグを取得する<br>
     * @return string 仮登録フラグ<br>
     */
    public function getTempRegistration() {
        return $this->tempRegistration;
    }

    /**
     * 仮登録フラグを設定する<br>
     * @param string $tempRegistration 仮登録フラグ<br>
     */
    public function setTempRegistration($tempRegistration) {
        $this->tempRegistration = $tempRegistration;
    }

    /**
     * 本人認証有効期限を取得する<br>
     * @return string 本人認証有効期限<br>
     */
    public function getVerifyTimeout() {
        return $this->verifyTimeout;
    }

    /**
     * 本人認証有効期限を設定する<br>
     * @param string $verifyTimeout 本人認証有効期限<br>
     */
    public function setVerifyTimeout($verifyTimeout) {
        $this->verifyTimeout = $verifyTimeout;
    }

    /**
     * 不正検知評価取引情報を取得する<br>
     * @return FraudDetectionRequestDto 不正検知評価取引情報<br>
     */
    public function getFraudDetectionRequest() {
        return $this->fraudDetectionRequest;
    }

    /**
     * 不正検知評価取引情報を設定する<br>
     * @param FraudDetectionRequestDto $fraudDetectionRequest 不正検知評価取引情報<br>
     */
    public function setFraudDetectionRequest($fraudDetectionRequest) {
        $this->fraudDetectionRequest = $fraudDetectionRequest;
    }

    /**
     * 不正検知実施フラグを取得する<br>
     * @return string 不正検知実施フラグ<br>
     */
    public function getWithFraudDetection() {
        return $this->withFraudDetection;
    }

    /**
     * 不正検知実施フラグを設定する<br>
     * @param string $withFraudDetection 不正検知実施フラグ<br>
     */
    public function setWithFraudDetection($withFraudDetection) {
        $this->withFraudDetection = $withFraudDetection;
    }

    /**
     * ログ用文字列(マスク済み)を設定する<br>
     * @param string $maskedLog ログ用文字列(マスク済み)<br>
     */
    public function _setMaskedLog($maskedLog) {
        $this->maskedLog = $maskedLog;
    }

    /**
     * ログ用文字列(マスク済み)を取得する<br>
     * @return string ログ用文字列(マスク済み)<br>
     */
    public function __toString() {
        return (string)$this->maskedLog;
    }


    /**
     * 拡張パラメータ<br>
     * 並列処理用の拡張パラメータを保持する。
     */
    private $optionParams;

    /**
     * 拡張パラメータリストを取得する<br>
     * @return OptionParams 拡張パラメータリスト<br>
     */
    public function getOptionParams()
    {
        return $this->optionParams;
    }

    /**
     * 拡張パラメータリストを設定する<br>
     * @param OptionParams $optionParams 拡張パラメータリスト<br>
     */
    public function setOptionParams($optionParams)
    {
        $this->optionParams = $optionParams;
    }

}
?>
