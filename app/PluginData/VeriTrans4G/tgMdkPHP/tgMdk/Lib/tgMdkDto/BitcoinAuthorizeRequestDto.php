<?php
/**
 * 決済サービスタイプ：ビットコイン、コマンド名：決済申込の要求Dtoクラス<br>
 *
 * @author Veritrans, Inc.
 *
 */
class BitcoinAuthorizeRequestDto extends AbstractPaymentRequestDto
{

    /**
     * 決済サービスタイプ<br>
     * 半角英数字<br>
     * 必須項目、固定値<br>
     */
    private $SERVICE_TYPE = "bitcoin";

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
     * 半角数字<br/>
     * - "coincheck"：Coincheck<br/>
     */
    private $serviceOptionType;

    /**
     * 決済金額<br>
     * 半角数字<br/>
     * 最大桁数：11<br/>
     * 決済金額を指定します。<br/>
     * - 1 以上かつ 99999999999 以下<br/>
     */
    private $amount;

    /**
     * 通貨<br>
     * 半角英字<br/>
     * 最大桁数：10<br/>
     * 決済通貨を指定します。<br/>
     * "JPY"：日本円<br/>
     */
    private $currency;

    /**
     * 商品名<br>
     * 文字列<br/>
     * 最大桁数：60<br/>
     * 商品名<br/>
     * - 最大文字数60byte<br/>
     */
    private $itemName;

    /**
     * 商品説明<br>
     * 文字列<br/>
     * 最大桁数：512<br/>
     * 商品説明<br/>
     * - 最大文字数512byte<br/>
     */
    private $itemDescription;


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
     * 通貨を取得する<br>
     * @return string 通貨<br>
     */
    public function getCurrency() {
        return $this->currency;
    }

    /**
     * 通貨を設定する<br>
     * @param string $currency 通貨<br>
     */
    public function setCurrency($currency) {
        $this->currency = $currency;
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
     * 商品説明を取得する<br>
     * @return string 商品説明<br>
     */
    public function getItemDescription() {
        return $this->itemDescription;
    }

    /**
     * 商品説明を設定する<br>
     * @param string $itemDescription 商品説明<br>
     */
    public function setItemDescription($itemDescription) {
        $this->itemDescription = $itemDescription;
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