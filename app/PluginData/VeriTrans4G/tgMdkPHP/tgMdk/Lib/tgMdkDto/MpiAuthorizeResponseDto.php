<?php
/**
 * 決済サービスタイプ：3Dセキュアカード連携、コマンド名：申込の応答Dtoクラス
 *
 * @author Veritrans, Inc.
 *
 */
class MpiAuthorizeResponseDto extends MdkBaseDto
{

    /** 
     * 決済サービスタイプ<br>
     */
    private $serviceType;

    /** 
     * 処理結果コード<br>
     */
    private $mstatus;

    /** 
     * 詳細結果コード<br>
     */
    private $vResultCode;

    /** 
     * エラーメッセージ<br>
     */
    private $merrMsg;

    /** 
     * 電文ID<br>
     */
    private $marchTxn;

    /** 
     * 取引ID<br>
     * 半角英数字<br>
     * 100 文字以内<br>
     * マーチャント側で取引を一意に表す注文管理ID<br>
     */
    private $orderId;

    /** 
     * 取引毎に付くID<br>
     */
    private $custTxn;

    /** 
     * MDK バージョン<br>
     * 半角英数字<br>
     * 5 桁<br>
     * 電文のバージョン番号。<br>
     */
    private $txnVersion;

    /** 
     * MPIトランザクションタイプ<br>
     */
    private $mpiTransactiontype;

    /** 
     * 要求カード番号<br>
     */
    private $reqCardNumber;

    /** 
     * 要求カード有効期限<br>
     */
    private $reqCardExpire;

    /** 
     * 要求取引金額<br>
     */
    private $reqAmount;

    /** 
     * 要求仕向先コード<br>
     */
    private $reqAcquirerCode;

    /** 
     * 要求商品コード<br>
     */
    private $reqItemCode;

    /** 
     * 要求カードセンター<br>
     */
    private $reqCardCenter;

    /** 
     * 要求JPO情報<br>
     */
    private $reqJpoInformation;

    /** 
     * 要求売上日<br>
     */
    private $reqSalesDay;

    /** 
     * 要求同時売上<br>
     */
    private $reqWithCapture;

    /** 
     * 要求セキュリティコード<br>
     */
    private $reqSecurityCode;

    /** 
     * 要求誕生日<br>
     */
    private $reqBirthday;

    /** 
     * 要求電話番号<br>
     */
    private $reqTel;

    /** 
     * 要求カナ名前（名）<br>
     */
    private $reqFirstKanaName;

    /** 
     * 要求カナ名前（姓）<br>
     */
    private $reqLastKanaName;

    /** 
     * 要求通貨単位<br>
     */
    private $reqCurrencyUnit;

    /** 
     * リダイレクションURI<br>
     */
    private $reqRedirectionUri;

    /** 
     * 要求HTTPユーザエージェント<br>
     */
    private $reqHttpUserAgent;

    /** 
     * 要求HTTPアセプト<br>
     */
    private $reqHttpAccept;

    /** 
     * 応答コンテンツ<br>
     * 無制限<br>
     * 本人認証が成功した場合に<br>
     * マーチャント側でコンシューマに対して応答するレスポンスです。<br>
     */
    private $resResponseContents;

    /** 
     * 応答会社ID<br>
     */
    private $resCorporationId;

    /** 
     * 応答ブランドID<br>
     */
    private $resBrandId;

    /** 
     * 応答3Dセキュアメッセージバージョン<br>
     */
    private $res3dMessageVersion;

    /** 
     * 本人認証要求日時<br>
     */
    private $authRequestDatetime;

    /** 
     * 本人認証応答日時<br>
     */
    private $authResponseDatetime;

    /** 
     * 結果XML(マスク済み)<br>
     * 半角英数字<br>
     */
    private  $resultXml;

    /**
     * PayNowIDオブジェクト<br>
     * オブジェクト<br>
     * PayNowID用項目を格納するオブジェクト<br>
     */
     private $payNowIdResponse;

    /**
     * 決済サービスタイプを取得する<br>
     * @return string 決済サービスタイプ<br>
     */
    public function getServiceType() {
        return $this->serviceType;
    }

    /**
     * 決済サービスタイプを設定する<br>
     * @param string $serviceType 決済サービスタイプ<br>
     */
    public function setServiceType($serviceType) {
        $this->serviceType = $serviceType;
    }

    /**
     * 処理結果コードを取得する<br>
     * @return string 処理結果コード<br>
     */
    public function getMstatus() {
        return $this->mstatus;
    }

    /**
     * 処理結果コードを設定する<br>
     * @param string $mstatus 処理結果コード<br>
     */
    public function setMstatus($mstatus) {
        $this->mstatus = $mstatus;
    }

    /**
     * 詳細結果コードを取得する<br>
     * @return string 詳細結果コード<br>
     */
    public function getVResultCode() {
        return $this->vResultCode;
    }

    /**
     * 詳細結果コードを設定する<br>
     * @param string $vResultCode 詳細結果コード<br>
     */
    public function setVResultCode($vResultCode) {
        $this->vResultCode = $vResultCode;
    }

    /**
     * エラーメッセージを取得する<br>
     * @return string エラーメッセージ<br>
     */
    public function getMerrMsg() {
        return $this->merrMsg;
    }

    /**
     * エラーメッセージを設定する<br>
     * @param string $merrMsg エラーメッセージ<br>
     */
    public function setMerrMsg($merrMsg) {
        $this->merrMsg = $merrMsg;
    }

    /**
     * 電文IDを取得する<br>
     * @return string 電文ID<br>
     */
    public function getMarchTxn() {
        return $this->marchTxn;
    }

    /**
     * 電文IDを設定する<br>
     * @param string $marchTxn 電文ID<br>
     */
    public function setMarchTxn($marchTxn) {
        $this->marchTxn = $marchTxn;
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
     * 取引毎に付くIDを取得する<br>
     * @return string 取引毎に付くID<br>
     */
    public function getCustTxn() {
        return $this->custTxn;
    }

    /**
     * 取引毎に付くIDを設定する<br>
     * @param string $custTxn 取引毎に付くID<br>
     */
    public function setCustTxn($custTxn) {
        $this->custTxn = $custTxn;
    }

    /**
     * MDK バージョンを取得する<br>
     * @return string MDK バージョン<br>
     */
    public function getTxnVersion() {
        return $this->txnVersion;
    }

    /**
     * MDK バージョンを設定する<br>
     * @param string $txnVersion MDK バージョン<br>
     */
    public function setTxnVersion($txnVersion) {
        $this->txnVersion = $txnVersion;
    }

    /**
     * MPIトランザクションタイプを取得する<br>
     * @return string MPIトランザクションタイプ<br>
     */
    public function getMpiTransactiontype() {
        return $this->mpiTransactiontype;
    }

    /**
     * MPIトランザクションタイプを設定する<br>
     * @param string $mpiTransactiontype MPIトランザクションタイプ<br>
     */
    public function setMpiTransactiontype($mpiTransactiontype) {
        $this->mpiTransactiontype = $mpiTransactiontype;
    }

    /**
     * 要求カード番号を取得する<br>
     * @return string 要求カード番号<br>
     */
    public function getReqCardNumber() {
        return $this->reqCardNumber;
    }

    /**
     * 要求カード番号を設定する<br>
     * @param string $reqCardNumber 要求カード番号<br>
     */
    public function setReqCardNumber($reqCardNumber) {
        $this->reqCardNumber = $reqCardNumber;
    }

    /**
     * 要求カード有効期限を取得する<br>
     * @return string 要求カード有効期限<br>
     */
    public function getReqCardExpire() {
        return $this->reqCardExpire;
    }

    /**
     * 要求カード有効期限を設定する<br>
     * @param string $reqCardExpire 要求カード有効期限<br>
     */
    public function setReqCardExpire($reqCardExpire) {
        $this->reqCardExpire = $reqCardExpire;
    }

    /**
     * 要求取引金額を取得する<br>
     * @return string 要求取引金額<br>
     */
    public function getReqAmount() {
        return $this->reqAmount;
    }

    /**
     * 要求取引金額を設定する<br>
     * @param string $reqAmount 要求取引金額<br>
     */
    public function setReqAmount($reqAmount) {
        $this->reqAmount = $reqAmount;
    }

    /**
     * 要求仕向先コードを取得する<br>
     * @return string 要求仕向先コード<br>
     */
    public function getReqAcquirerCode() {
        return $this->reqAcquirerCode;
    }

    /**
     * 要求仕向先コードを設定する<br>
     * @param string $reqAcquirerCode 要求仕向先コード<br>
     */
    public function setReqAcquirerCode($reqAcquirerCode) {
        $this->reqAcquirerCode = $reqAcquirerCode;
    }

    /**
     * 要求商品コードを取得する<br>
     * @return string 要求商品コード<br>
     */
    public function getReqItemCode() {
        return $this->reqItemCode;
    }

    /**
     * 要求商品コードを設定する<br>
     * @param string $reqItemCode 要求商品コード<br>
     */
    public function setReqItemCode($reqItemCode) {
        $this->reqItemCode = $reqItemCode;
    }

    /**
     * 要求カードセンターを取得する<br>
     * @return string 要求カードセンター<br>
     */
    public function getReqCardCenter() {
        return $this->reqCardCenter;
    }

    /**
     * 要求カードセンターを設定する<br>
     * @param string $reqCardCenter 要求カードセンター<br>
     */
    public function setReqCardCenter($reqCardCenter) {
        $this->reqCardCenter = $reqCardCenter;
    }

    /**
     * 要求JPO情報を取得する<br>
     * @return string 要求JPO情報<br>
     */
    public function getReqJpoInformation() {
        return $this->reqJpoInformation;
    }

    /**
     * 要求JPO情報を設定する<br>
     * @param string $reqJpoInformation 要求JPO情報<br>
     */
    public function setReqJpoInformation($reqJpoInformation) {
        $this->reqJpoInformation = $reqJpoInformation;
    }

    /**
     * 要求売上日を取得する<br>
     * @return string 要求売上日<br>
     */
    public function getReqSalesDay() {
        return $this->reqSalesDay;
    }

    /**
     * 要求売上日を設定する<br>
     * @param string $reqSalesDay 要求売上日<br>
     */
    public function setReqSalesDay($reqSalesDay) {
        $this->reqSalesDay = $reqSalesDay;
    }

    /**
     * 要求同時売上を取得する<br>
     * @return string 要求同時売上<br>
     */
    public function getReqWithCapture() {
        return $this->reqWithCapture;
    }

    /**
     * 要求同時売上を設定する<br>
     * @param string $reqWithCapture 要求同時売上<br>
     */
    public function setReqWithCapture($reqWithCapture) {
        $this->reqWithCapture = $reqWithCapture;
    }

    /**
     * 要求セキュリティコードを取得する<br>
     * @return string 要求セキュリティコード<br>
     */
    public function getReqSecurityCode() {
        return $this->reqSecurityCode;
    }

    /**
     * 要求セキュリティコードを設定する<br>
     * @param string $reqSecurityCode 要求セキュリティコード<br>
     */
    public function setReqSecurityCode($reqSecurityCode) {
        $this->reqSecurityCode = $reqSecurityCode;
    }

    /**
     * 要求誕生日を取得する<br>
     * @return string 要求誕生日<br>
     */
    public function getReqBirthday() {
        return $this->reqBirthday;
    }

    /**
     * 要求誕生日を設定する<br>
     * @param string $reqBirthday 要求誕生日<br>
     */
    public function setReqBirthday($reqBirthday) {
        $this->reqBirthday = $reqBirthday;
    }

    /**
     * 要求電話番号を取得する<br>
     * @return string 要求電話番号<br>
     */
    public function getReqTel() {
        return $this->reqTel;
    }

    /**
     * 要求電話番号を設定する<br>
     * @param string $reqTel 要求電話番号<br>
     */
    public function setReqTel($reqTel) {
        $this->reqTel = $reqTel;
    }

    /**
     * 要求カナ名前（名）を取得する<br>
     * @return string 要求カナ名前（名）<br>
     */
    public function getReqFirstKanaName() {
        return $this->reqFirstKanaName;
    }

    /**
     * 要求カナ名前（名）を設定する<br>
     * @param string $reqFirstKanaName 要求カナ名前（名）<br>
     */
    public function setReqFirstKanaName($reqFirstKanaName) {
        $this->reqFirstKanaName = $reqFirstKanaName;
    }

    /**
     * 要求カナ名前（姓）を取得する<br>
     * @return string 要求カナ名前（姓）<br>
     */
    public function getReqLastKanaName() {
        return $this->reqLastKanaName;
    }

    /**
     * 要求カナ名前（姓）を設定する<br>
     * @param string $reqLastKanaName 要求カナ名前（姓）<br>
     */
    public function setReqLastKanaName($reqLastKanaName) {
        $this->reqLastKanaName = $reqLastKanaName;
    }

    /**
     * 要求通貨単位を取得する<br>
     * @return string 要求通貨単位<br>
     */
    public function getReqCurrencyUnit() {
        return $this->reqCurrencyUnit;
    }

    /**
     * 要求通貨単位を設定する<br>
     * @param string $reqCurrencyUnit 要求通貨単位<br>
     */
    public function setReqCurrencyUnit($reqCurrencyUnit) {
        $this->reqCurrencyUnit = $reqCurrencyUnit;
    }

    /**
     * リダイレクションURIを取得する<br>
     * @return string リダイレクションURI<br>
     */
    public function getReqRedirectionUri() {
        return $this->reqRedirectionUri;
    }

    /**
     * リダイレクションURIを設定する<br>
     * @param string $reqRedirectionUri リダイレクションURI<br>
     */
    public function setReqRedirectionUri($reqRedirectionUri) {
        $this->reqRedirectionUri = $reqRedirectionUri;
    }

    /**
     * 要求HTTPユーザエージェントを取得する<br>
     * @return string 要求HTTPユーザエージェント<br>
     */
    public function getReqHttpUserAgent() {
        return $this->reqHttpUserAgent;
    }

    /**
     * 要求HTTPユーザエージェントを設定する<br>
     * @param string $reqHttpUserAgent 要求HTTPユーザエージェント<br>
     */
    public function setReqHttpUserAgent($reqHttpUserAgent) {
        $this->reqHttpUserAgent = $reqHttpUserAgent;
    }

    /**
     * 要求HTTPアセプトを取得する<br>
     * @return string 要求HTTPアセプト<br>
     */
    public function getReqHttpAccept() {
        return $this->reqHttpAccept;
    }

    /**
     * 要求HTTPアセプトを設定する<br>
     * @param string $reqHttpAccept 要求HTTPアセプト<br>
     */
    public function setReqHttpAccept($reqHttpAccept) {
        $this->reqHttpAccept = $reqHttpAccept;
    }

    /**
     * 応答コンテンツを取得する<br>
     * @return string 応答コンテンツ<br>
     */
    public function getResResponseContents() {
        return $this->resResponseContents;
    }

    /**
     * 応答コンテンツを設定する<br>
     * @param string $resResponseContents 応答コンテンツ<br>
     */
    public function setResResponseContents($resResponseContents) {
        $this->resResponseContents = $resResponseContents;
    }

    /**
     * 応答会社IDを取得する<br>
     * @return string 応答会社ID<br>
     */
    public function getResCorporationId() {
        return $this->resCorporationId;
    }

    /**
     * 応答会社IDを設定する<br>
     * @param string $resCorporationId 応答会社ID<br>
     */
    public function setResCorporationId($resCorporationId) {
        $this->resCorporationId = $resCorporationId;
    }

    /**
     * 応答ブランドIDを取得する<br>
     * @return string 応答ブランドID<br>
     */
    public function getResBrandId() {
        return $this->resBrandId;
    }

    /**
     * 応答ブランドIDを設定する<br>
     * @param string $resBrandId 応答ブランドID<br>
     */
    public function setResBrandId($resBrandId) {
        $this->resBrandId = $resBrandId;
    }

    /**
     * 応答3Dセキュアメッセージバージョンを取得する<br>
     * @return string 応答3Dセキュアメッセージバージョン<br>
     */
    public function getRes3dMessageVersion() {
        return $this->res3dMessageVersion;
    }

    /**
     * 応答3Dセキュアメッセージバージョンを設定する<br>
     * @param string $res3dMessageVersion 応答3Dセキュアメッセージバージョン<br>
     */
    public function setRes3dMessageVersion($res3dMessageVersion) {
        $this->res3dMessageVersion = $res3dMessageVersion;
    }

    /**
     * 本人認証要求日時を取得する<br>
     * @return string 本人認証要求日時<br>
     */
    public function getAuthRequestDatetime() {
        return $this->authRequestDatetime;
    }

    /**
     * 本人認証要求日時を設定する<br>
     * @param string $authRequestDatetime 本人認証要求日時<br>
     */
    public function setAuthRequestDatetime($authRequestDatetime) {
        $this->authRequestDatetime = $authRequestDatetime;
    }

    /**
     * 本人認証応答日時を取得する<br>
     * @return string 本人認証応答日時<br>
     */
    public function getAuthResponseDatetime() {
        return $this->authResponseDatetime;
    }

    /**
     * 本人認証応答日時を設定する<br>
     * @param string $authResponseDatetime 本人認証応答日時<br>
     */
    public function setAuthResponseDatetime($authResponseDatetime) {
        $this->authResponseDatetime = $authResponseDatetime;
    }

    /**
     * 結果XML(マスク済み)を設定する<br>
     * @param string $resultXml 結果XML(マスク済み)<br>
     */
    public function _setResultXml($resultXml) {
        $this->resultXml = $resultXml;
    }

    /**
     * 結果XML(マスク済み)を取得する<br>
     * @return string 結果XML(マスク済み)<br>
     */
    public function __toString() {
        return (string)$this->resultXml;
    }


    /**
     * レスポンスのXMLからTradURLを取得します<br>
     *
     * @return string レスポンスのXMLに含まれていた広告用（Trad）URL<br>
     *                エレメントが無いか、エレメントに内容が無ければnullを返す<br>
     */
    public function getTradUrl() {
        $processor = new TGMDK_ResElementProcessor($this->__toString());
        return $processor->get_trad_url();
    }

    /**
     * PayNowIDオブジェクトを設定する<br>
     * @param PayNowIdResponse $payNowIdResponse PayNowIDオブジェクト<br>
     */
    public function setPayNowIdResponse($payNowIdResponse) {
        $this -> payNowIdResponse = $payNowIdResponse;
    }
       
    /**
    * PayNowIDオブジェクトを取得する<br>
    * @return PayNowIdResponse PayNowIDオブジェクト<br>
    */
    public function getPayNowIdResponse() {
        return $this -> payNowIdResponse;
    }
    
}
?>
