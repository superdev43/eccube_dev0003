<?php
/**
 * 検索条件:3Dセキュアカード連携検索パラメータクラス<br>
 *
 * @author Veritrans, Inc.
 */
class MpiSearchParameter {

    /** 
     * 詳細オーダー決済状態<br>
     */
    private $detailOrderType;

    /**
     * 応答3D トランザクションID<br>
     */
    private $res3dTransactionId;

    /**
     * 応答3D トランザクションステータス<br>    
     */
    private $res3dTransactionStatus;


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
     * 応答3D ECI<br>
     */
    private $res3dEci;

    /**
     * 応答3D ECIを取得する<br>
     * 
     * @return string 応答3D ECI
     */
    public Function getRes3dEci() {
        return $this->res3dEci;
    }

    /**
     * 応答3D ECIを設定する<br>
     * @param string $res3dEci 応答3D ECI
     */
    public Function setRes3dEci($res3dEci) {
        $this->res3dEci = $res3dEci;
    }

    /**
     * 応答3D トランザクションステータスを取得する<br>
     * 
     * @return string 応答3D トランザクションステータス<br>
     */
    public Function getRes3dTransactionStatus() {
        return $this->res3dTransactionStatus;
    }

    /**
     * 応答3D トランザクションステータスを設定する<br>
     * 
     * @param string $res3dTransactionStatus 応答3D トランザクションステータス<br>
     */
    public Function setRes3dTransactionStatus($res3dTransactionStatus) {
        $this->res3dTransactionStatus = $res3dTransactionStatus;
    }

    /**
     * 応答3D トランザクションIDを取得する<br>
     * 
     * @return string 応答3D トランザクションID<br>
     */
    public Function getRes3dTransactionId() {
        return $this->res3dTransactionId;
    }

    /**
     * 応答3D トランザクションIDを設定する<br>
     * 
     * @param string $res3dTransactionId 応答3DトランザクションID<br>
     */
    public Function setRes3dTransactionId($res3dTransactionId) {
        $this->res3dTransactionId = $res3dTransactionId;
    }

}
?>