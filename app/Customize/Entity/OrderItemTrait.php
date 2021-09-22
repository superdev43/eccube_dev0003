<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\OrderItem")
 */
trait OrderItemTrait
{
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $cus_shipping_id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $syn_delivery_fee_total;

     /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $cus_order_status_id;

     /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $cus_shipping_status_id;

     /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $is_order_csv_download;

     /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $is_mail_sent;

    /**
     * @var \Plugin\CustomShipping\Entity\CusShipping
     *
     * @ORM\ManyToOne(targetEntity="\Plugin\CustomShipping\Entity\CusShipping")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="cus_shipping_id", referencedColumnName="id")
     * })
     */
    private $CusShipping;

    /**
     * Get cus_shipping_id
     * @return int|null
     */
    public function getCusShippingId()
    {
        return $this->cus_shipping_id;
    }

    /**
     * Get syn_delivery_fee_total
     * @return int|null
     */
    public function getSynDeliveryFeeTotal()
    {
        return $this->syn_delivery_fee_total;
    }

    /**
     * Get cus_shipping_status_id
     * @return int|null
     */
    public function getCusShippingStatusId()
    {
        return $this->cus_shipping_status_id;
    }

    /**
     * Get cus_order_status_id
     * @return int
     */
    public function getCusOrderStatusId()
    {
        return $this->cus_order_status_id;
    }


    /**
     * Set cus_shipping_status_id
     * 
     * @param int|null $cus_shipping_status_id
     * 
     * @return OrderItemTrait
     * 
     */
    public function setCusShippingStatusId($cus_shipping_status_id = null){
        $this->cus_shipping_status_id = $cus_shipping_status_id;
        return $this; 
    }

    /**
     * Set syn_delivery_fee_total
     * 
     * @param int|null $syn_delivery_fee_total
     * 
     * @return OrderItemTrait
     * 
     */
    public function setSynDeliveryFeeTotal($syn_delivery_fee_total = null){
        $this->syn_delivery_fee_total = $syn_delivery_fee_total;
        return $this; 
    }
    /**
     * Set cus_shipping_id
     * 
     * @param int|null $cus_shipping_id
     * 
     * @return OrderItemTrait
     * 
     */
    public function setCusShippingId($cus_shipping_id = null){
        $this->cus_shipping_id = $cus_shipping_id;
        return $this; 
    }

    /**
     * Set cus_order_status_id
     * 
     * @param int $cus_order_status_id
     * 
     * @return OrderItemTrait
     * 
     */
    public function setCusOrderStatusId($cus_order_status_id)
    {
        $this->cus_order_status_id = $cus_order_status_id;
        return $this;
    }

    /**
     * Set is_order_csv_download
     * 
     * @param int $is_order_csv_download
     * 
     * @return OrderItemTrait
     * 
     */
    public function setIsOrderCsvDownload($is_order_csv_download)
    {
        $this->is_order_csv_download = $is_order_csv_download;
        return $this;
    }
    
    /**
     * Set is_mail_sent
     * 
     * @param int $is_mail_sent
     * 
     * @return OrderItemTrait
     * 
     */
    public function setIsMailSent($is_mail_sent)
    {
        $this->is_mail_sent = $is_mail_sent;
        return $this;
    }

    /**
     * Set CusShipping.
     *
     * @param \Plugin\CustomShipping\Entity\CusShipping|null $product
     *
     * @return OrderItem
     */
    public function setCusShipping(\Plugin\CustomShipping\Entity\CusShipping $cusShipping = null)
    {
        $this->CusShipping = $cusShipping;

        return $this;
    }

    /**
     * Get CusShipping.
     *
     * @return \Plugin\CustomShipping\Entity\CusShipping|null
     */
    public function getCusShipping()
    {
        return $this->CusShipping;
    }
}
