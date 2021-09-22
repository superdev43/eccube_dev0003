<?php
/**
 * 決済サービスタイプ：Tenpay、コマンド名：与信の要求Dtoクラス<br>
 *
 * @author Veritrans, Inc.
 *
 */
class TenpayAuthorizeRequestDto extends AbstractPaymentRequestDto
{

    /**
     * 決済サービスタイプ<br>
     * 半角英数字<br>
     * 必須項目、固定値<br>
     */
    private $SERVICE_TYPE = "tenpay";

    /**
     * 決済サービスコマンド<br>
     * 半角英数字<br>
     * 必須項目、固定値<br>
     */
    private $SERVICE_COMMAND = "Authorize";

    /**
     * 取引ID<br>
     * 半角英数字<br/>
     * 最大桁数：100<br/>
     * - マーチャント側で取引を一意に表す注文管理IDを指定します。<br/>
     * - 申込処理ごとに一意である必要があります。<br/>
     * - 半角英数字、“-”（ハイフン）、“_”（アンダースコア）も使用可能です。<br/>
     */
    private $orderId;

    /**
     * サービスオプションタイプ<br>
     * 半角英数字<br/>
     * - "barcode":バーコード決済<br/>
     */
    private $serviceOptionType;

    /**
     * 決済金額<br>
     * 半角数字<br/>
     * 最大桁数：7<br/>
     * 決済金額を指定します。<br/>
     * - 1 以上かつ 9999999(7桁) 以下<br/>
     */
    private $amount;

    /**
     * 与信同時売上フラグ<br>
     * 英字（boolean）<br/>
     * - true : 与信同時売上（設定可能な値は"true"のみです。）<br/>
     * ※未指定の場合は、true:与信同時売上。<br/>
     */
    private $withCapture;

    /**
     * デバイス情報<br>
     * 文字列<br/>
     * 最大桁数：32<br/>
     * デバイス情報を指定します。<br/>
     * - 最大文字数:32byte<br/>
     */
    private $deviceInfo;

    /**
     * 商品名<br>
     * 文字列<br/>
     * 最大桁数：32<br/>
     * 商品名<br/>
     * - 最大文字数：32byte<br/>
     */
    private $itemName;

    /**
     * 商品詳細<br>
     * 文字列<br/>
     * 最大桁数：1000<br/>
     * 商品詳細<br/>
     * - 最大文字数：1000byte<br/>
     */
    private $itemDetail;

    /**
     * 商品タグ<br>
     * 文字列<br/>
     * 最大桁数：32<br/>
     * 商品に付与できるタグ情報。<br/>
     * - 最大文字数:32byte<br/>
     */
    private $itemLabel;

    /**
     * 承認コード<br>
     * 半角英数字<br/>
     * 最大桁数：128<br/>
     * バーコード決済を利用する際のQRコード、バーコードの値を指定します。<br/>
     */
    private $authorizationCode;

    /**
     * レスポンスタイプ<br>
     * 半角数字<br/>
     * 最大桁数：1<br/>
     * レスポンスのタイプを指定します。<br/>
     * "0"：取引確定時にレスポンスを返却する。<br/>
     * "1"：即時にレスポンスを返却する。<br/>
     * ※未指定の場合は、0：取引確定時にレスポンスを返却する。<br/>
     */
    private $responseType;

    /**
     * 店舗ID<br>
     * 半角英数字<br/>
     * 最大桁数：32<br/>
     * 店舗IDを指定します。<br/>
     * - 半角英数字、“-”（ハイフン）、“_”（アンダースコア）も使用可能です。<br/>
     */
    private $storeId;

    /**
     * 店舗名<br>
     * 文字列<br/>
     * 最大桁数：64<br/>
     * 店舗名を指定します。<br/>
     * - 最大文字数：64byte<br/>
     */
    private $storeName;


    /**
     * ログ用文字列(マスク済み)<br>
     * 半角英数字<br>
     */
    private $maskedLog;


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
     * サービスオプションタイプを取得する<br>
     * @return string サービスオプションタイプ<br>
     */
    public function getServiceOptionType() {
        return $this->serviceOptionType;
    }

    /**
     * サービスオプションタイプを設定する<br>
     * @param string $serviceOptionType サービスオプションタイプ<br>
     */
    public function setServiceOptionType($serviceOptionType) {
        $this->serviceOptionType = $serviceOptionType;
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
     * 与信同時売上フラグを取得する<br>
     * @return string 与信同時売上フラグ<br>
     */
    public function getWithCapture() {
        return $this->withCapture;
    }

    /**
     * 与信同時売上フラグを設定する<br>
     * @param string $withCapture 与信同時売上フラグ<br>
     */
    public function setWithCapture($withCapture) {
        $this->withCapture = $withCapture;
    }

    /**
     * デバイス情報を取得する<br>
     * @return string デバイス情報<br>
     */
    public function getDeviceInfo() {
        return $this->deviceInfo;
    }

    /**
     * デバイス情報を設定する<br>
     * @param string $deviceInfo デバイス情報<br>
     */
    public function setDeviceInfo($deviceInfo) {
        $this->deviceInfo = $deviceInfo;
    }

    /**
     * 商品名を取得する<br>
     * @return string 商品名<br>
     */
    public function getItemName() {
        return $this->itemName;
    }

    /**
     * 商品名を設定する<br>
     * @param string $itemName 商品名<br>
     */
    public function setItemName($itemName) {
        $this->itemName = $itemName;
    }

    /**
     * 商品詳細を取得する<br>
     * @return string 商品詳細<br>
     */
    public function getItemDetail() {
        return $this->itemDetail;
    }

    /**
     * 商品詳細を設定する<br>
     * @param string $itemDetail 商品詳細<br>
     */
    public function setItemDetail($itemDetail) {
        $this->itemDetail = $itemDetail;
    }

    /**
     * 商品タグを取得する<br>
     * @return string 商品タグ<br>
     */
    public function getItemLabel() {
        return $this->itemLabel;
    }

    /**
     * 商品タグを設定する<br>
     * @param string $itemLabel 商品タグ<br>
     */
    public function setItemLabel($itemLabel) {
        $this->itemLabel = $itemLabel;
    }

    /**
     * 承認コードを取得する<br>
     * @return string 承認コード<br>
     */
    public function getAuthorizationCode() {
        return $this->authorizationCode;
    }

    /**
     * 承認コードを設定する<br>
     * @param string $authorizationCode 承認コード<br>
     */
    public function setAuthorizationCode($authorizationCode) {
        $this->authorizationCode = $authorizationCode;
    }

    /**
     * レスポンスタイプを取得する<br>
     * @return string レスポンスタイプ<br>
     */
    public function getResponseType() {
        return $this->responseType;
    }

    /**
     * レスポンスタイプを設定する<br>
     * @param string $responseType レスポンスタイプ<br>
     */
    public function setResponseType($responseType) {
        $this->responseType = $responseType;
    }

    /**
     * 店舗IDを取得する<br>
     * @return string 店舗ID<br>
     */
    public function getStoreId() {
        return $this->storeId;
    }

    /**
     * 店舗IDを設定する<br>
     * @param string $storeId 店舗ID<br>
     */
    public function setStoreId($storeId) {
        $this->storeId = $storeId;
    }

    /**
     * 店舗名を取得する<br>
     * @return string 店舗名<br>
     */
    public function getStoreName() {
        return $this->storeName;
    }

    /**
     * 店舗名を設定する<br>
     * @param string $storeName 店舗名<br>
     */
    public function setStoreName($storeName) {
        $this->storeName = $storeName;
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

}
?>