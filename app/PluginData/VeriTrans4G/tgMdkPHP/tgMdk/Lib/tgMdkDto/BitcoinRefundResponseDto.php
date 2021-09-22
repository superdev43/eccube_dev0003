<?php
/**
 * 決済サービスタイプ：ビットコイン、コマンド名：返金の応答Dtoクラス<br>
 *
 * @author Veritrans, Inc.
 *
 */
class BitcoinRefundResponseDto extends MdkBaseDto
{

    /**
     * 決済サービスタイプ<br>
     * 半角英数字<br/>
     * 最大桁数：10<br/>
     * 決済サービスの区分が固定値として指定されます。<br/>
     * "bitcoin"： ビットコイン決済<br/>
     */
    private $serviceType;

    /**
     * 処理結果コード<br>
     * 半角英数字<br/>
     * 最大桁数：32<br/>
     * 返金請求処理後、応答電文に含まれる値。<br/>
     * 以下の処理結果のいずれかが格納される<br/>
     * - success：正常終了<br/>
     * - failure：異常終了<br/>
     */
    private $mstatus;

    /**
     * 詳細結果コード<br>
     * 半角英数字<br/>
     * 最大桁数：16<br/>
     * 処理結果を詳細に表すコードを返却します。<br/>
     * <br/>
     * 4桁ずつ4つのブロックで構成され、各ブロックでサービス毎の処理結果を表します。<br/>
     */
    private $vResultCode;

    /**
     * エラーメッセージ<br>
     * 文字列<br/>
     * 最大桁数：300<br/>
     * 処理結果に対するメッセージを返却します。<br/>
     */
    private $merrMsg;

    /**
     * 電文ID<br>
     * 半角英数字<br/>
     * 最大桁数：100<br/>
     */
    private $marchTxn;

    /**
     * 取引ID<br>
     * 半角英数字<br/>
     * 最大桁数：100<br/>
     */
    private $orderId;

    /**
     * 取引毎に付くID<br>
     * 半角英数字<br/>
     * 最大桁数：100<br/>
     */
    private $custTxn;

    /**
     * MDKバージョン<br>
     * 半角英数字<br/>
     * 最大桁数：5<br/>
     * 電文のバージョン番号を返却します。<br/>
     */
    private $txnVersion;

    /**
     * 手数料負担タイプ<br>
     * 半角数字<br/>
     * 最大桁数：1<br/>
     * ビットコイン決済事業者に要求した手数料負担タイプを返却します。<br/>
     */
    private $chargeBearerType;

    /**
     * 返金金額（BTC）<br>
     * 半角数字<br/>
     * 最大桁数：17<br/>
     * ビットコインの通貨単位に換算された返金金額を返却します（小数点を含む）<br/>
     */
    private $amountBtc;

    /**
     * 残高<br>
     * 半角数字<br/>
     * 最大桁数：11<br/>
     * 残高<br/>
     * ※ 現在は未使用の項目です。<br/>
     */
    private $balance;

    /**
     * 残高（BTC）<br>
     * 半角数字<br/>
     * 最大桁数：17<br/>
     * ビットコインの通貨単位に換算された残高（小数点を含む）<br/>
     * ※ 現在は未使用の項目です。<br/>
     */
    private $balanceBtc;

    /**
     * 返金日時<br>
     * 半角数字<br/>
     * 最大桁数：14<br/>
     * ビットコイン決済で処理が完了した日時を返却します。<br/>
     */
    private $refundDatetime;


    /**
     * 結果XML(マスク済み)<br>
     * 半角英数字<br>
     */
    private $resultXml;

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
     * MDKバージョンを取得する<br>
     * @return string MDKバージョン<br>
     */
    public function getTxnVersion() {
        return $this->txnVersion;
    }

    /**
     * MDKバージョンを設定する<br>
     * @param string $txnVersion MDKバージョン<br>
     */
    public function setTxnVersion($txnVersion) {
        $this->txnVersion = $txnVersion;
    }

    /**
     * 手数料負担タイプを取得する<br>
     * @return string 手数料負担タイプ<br>
     */
    public function getChargeBearerType() {
        return $this->chargeBearerType;
    }

    /**
     * 手数料負担タイプを設定する<br>
     * @param string $chargeBearerType 手数料負担タイプ<br>
     */
    public function setChargeBearerType($chargeBearerType) {
        $this->chargeBearerType = $chargeBearerType;
    }

    /**
     * 返金金額（BTC）を取得する<br>
     * @return string 返金金額（BTC）<br>
     */
    public function getAmountBtc() {
        return $this->amountBtc;
    }

    /**
     * 返金金額（BTC）を設定する<br>
     * @param string $amountBtc 返金金額（BTC）<br>
     */
    public function setAmountBtc($amountBtc) {
        $this->amountBtc = $amountBtc;
    }

    /**
     * 残高を取得する<br>
     * @return string 残高<br>
     */
    public function getBalance() {
        return $this->balance;
    }

    /**
     * 残高を設定する<br>
     * @param string $balance 残高<br>
     */
    public function setBalance($balance) {
        $this->balance = $balance;
    }

    /**
     * 残高（BTC）を取得する<br>
     * @return string 残高（BTC）<br>
     */
    public function getBalanceBtc() {
        return $this->balanceBtc;
    }

    /**
     * 残高（BTC）を設定する<br>
     * @param string $balanceBtc 残高（BTC）<br>
     */
    public function setBalanceBtc($balanceBtc) {
        $this->balanceBtc = $balanceBtc;
    }

    /**
     * 返金日時を取得する<br>
     * @return string 返金日時<br>
     */
    public function getRefundDatetime() {
        return $this->refundDatetime;
    }

    /**
     * 返金日時を設定する<br>
     * @param string $refundDatetime 返金日時<br>
     */
    public function setRefundDatetime($refundDatetime) {
        $this->refundDatetime = $refundDatetime;
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
     * PayNowIDオブジェクトを設定する<br>
     * @param PayNowIdResponse $PayNowIDオブジェクト<br>
     */
    public function setPayNowIdResponse($payNowIdResponse) {
        $this->payNowIdResponse = $payNowIdResponse;
    }

    /**
     * PayNowIDオブジェクトを取得する<br>
     * @return PayNowIdResponse PayNowIDオブジェクト<br>
     */
    public function getPayNowIdResponse() {
        return $this->payNowIdResponse;
    }

}
?>