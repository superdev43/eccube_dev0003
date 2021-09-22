<?php
/**
 * 検索条件:ビットコイン検索パラメータクラス<br>
 *
 * @author Veritrans, Inc.
 *
 */
class BitcoinSearchParameter
{

    /**
     * 詳細オーダー決済状態<br>
     */
    private $detailOrderType;

    /**
     * 詳細コマンドタイプ名<br>
     */
    private $detailCommandType;

    /**
     * ビットコインサービスタイプ<br>
     */
    private $bitcoinServiceType;

    /**
     * 詳細オーダー決済状態を取得する<br>
     *
     * @return string[] 詳細オーダー決済状態<br>
     */
    public function getDetailOrderType() {
        return $this->detailOrderType;
    }

    /**
     * 詳細オーダー決済状態を設定する<br>
     *
     * @param string[] $detailOrderType 詳細オーダー決済状態<br>
     */
    public function setDetailOrderType($detailOrderType) {
        $this->detailOrderType = $detailOrderType;
    }

    /**
     * 詳細コマンドタイプ名を取得する<br>
     *
     * @return string[] 詳細コマンドタイプ名<br>
     */
    public function getDetailCommandType() {
        return $this->detailCommandType;
    }

    /**
     * 詳細コマンドタイプ名を設定する<br>
     *
     * @param string[] $detailCommandType 詳細コマンドタイプ名<br>
     */
    public function setDetailCommandType($detailCommandType) {
        $this->detailCommandType = $detailCommandType;
    }

    /**
     * ビットコインサービスタイプを取得する<br>
     *
     * @return string[] ビットコインサービスタイプ<br>
     */
    public function getBitcoinServiceType() {
        return $this->bitcoinServiceType;
    }

    /**
     * ビットコインサービスタイプを設定する<br>
     *
     * @param string[] $bitcoinServiceType ビットコインサービスタイプ<br>
     */
    public function setBitcoinServiceType($bitcoinServiceType) {
        $this->bitcoinServiceType = $bitcoinServiceType;
    }

}
?>