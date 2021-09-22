<?php

namespace Plugin\CustomShipping\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Plugin\CustomShipping\Entity\CusShipping')) {

}
/**
 * CusShipping
 *
 * @ORM\Table(name="cus_shipping")

 * @ORM\Entity(repositoryClass="Plugin\CustomShipping\Repository\CusShippingRepository")
 */

class CusShipping extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var integer
     * 
     * @ORM\Column(name="cus_shipping_charge", type="integer")
     */
    private $cus_shipping_charge;

    /**
     * @var string
     * 
     * @ORM\Column(name="cus_track_number", type="string", length=50)
     */
    private $cus_track_number;

    /**
     * @var date
     * 
     * @ORM\Column(name="cus_shipping_date", type="date", nullable=true)
     */
    private $cus_shipping_date;

    /**
     * @var integer
     * 
     * @ORM\Column(name="cus_order_id", type="integer")
     */
    private $cus_order_id;

    /**
     * @var integer
     * 
     * @ORM\Column(name="cus_sub_total", type="integer")
     */
    private $cus_sub_total;
    
      /**
     * @var integer
     * 
     * @ORM\Column(name="cus_total", type="integer")
     */
    private $cus_total;

      /**
     * @var integer
     * 
     * @ORM\Column(name="cus_total_by_tax", type="integer")
     */
    private $cus_total_by_tax;

      /**
     * @var integer
     * 
     * @ORM\Column(name="cus_payment_total", type="integer")
     */
    private $cus_payment_total;

      /**
     * @var integer
     * 
     * @ORM\Column(name="cus_shipping_status", type="integer")
     */
    private $cus_shipping_status;











    /**
     * Get id.
     *
     * @return Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set cus_shipping_charge
     * 
     * @param int|null $cus_shipping_charge
     * 
     * @return CusShipping
     * 
     */
    public function setCusShippingCharge($cus_shipping_charge = null)
    {
        $this->cus_shipping_charge = $cus_shipping_charge;
        return $this;
    }

    /**
     * Get cus_shipping_charge
     * @return int|null
     */
    public function getCusShippingCharge()
    {
        return $this->cus_shipping_charge;
    }


    /**
     * Set cus_track_number
     * 
     * @param string|null $cus_track_number
     * 
     * @return CusShipping
     */
    public function setCusTrackNumber($cus_track_number = null)
    {
        $this->cus_track_number = $cus_track_number;
        return $this;
    }


    /**
     * Get cus_track_number
     * @return string|null
     */
    public function getCusTrackNumber()
    {
        return $this->cus_track_number;
    }

    /**
     * Set cus_shipping_date
     * 
     * @param data $cus_shipping_date
     * 
     * @return CusShipping
     * 
     */
    public function setCusShippingDate($cus_shipping_date = null)
    {
        $this->cus_shipping_date = $cus_shipping_date;
        return $this;
    }

    /**
     * Get cus_shipping_date
     * @return data|null
     */
    public function getCusShippingDate()
    {
        return $this->cus_shipping_date;
    }

    /**
     * Set cus_order_id
     * 
     * @param int|null $cus_order_id
     * 
     * @return CusShipping
     */
    public function setCusOrderId($cus_order_id=null){
        $this->cus_order_id = $cus_order_id;
        return $this;
    }

    /**
     * Get cus_order_id
     * 
     * @return int|null
     * 
     * 
     */
    public function getCusOrderId(){
        return $this->cus_order_id;
    }

    /**
     * Set cus_sub_total
     * 
     * @param int|null $cus_sub_total
     * 
     * @return CusShipping
     */
    public function setCusSubTotal($cus_sub_total=null){
        $this->cus_sub_total = $cus_sub_total;
        return $this;
    }

    /**
     * Get cus_sub_total
     * 
     * @return int|null
     * 
     * 
     */
    public function getCusSubTotal(){
        return $this->cus_sub_total;
    }

    /**
     * Set cus_total
     * 
     * @param int|null $cus_total
     * 
     * @return CusShipping
     */
    public function setCusTotal($cus_total=null){
        $this->cus_total = $cus_total;
        return $this;
    }

    /**
     * Get cus_total
     * 
     * @return int|null
     * 
     * 
     */
    public function getCusTotal(){
        return $this->cus_total;
    }


    /**
     * Set cus_total_by_tax
     * 
     * @param int|null $cus_total_by_tax
     * 
     * @return CusShipping
     */
    public function setCusTotalByTax($cus_total_by_tax=null){
        $this->cus_total_by_tax = $cus_total_by_tax;
        return $this;
    }

    /**
     * Get cus_total_by_tax
     * 
     * @return int|null
     * 
     * 
     */
    public function getCusTotalByTax(){
        return $this->cus_total_by_tax;
    }


    /**
     * Set cus_payment_total
     * 
     * @param int|null $cus_payment_total
     * 
     * @return CusShipping
     */
    public function setCusPaymentTotal($cus_payment_total=null){
        $this->cus_payment_total = $cus_payment_total;
        return $this;
    }

    /**
     * Get cus_payment_total
     * 
     * @return int|null
     * 
     * 
     */
    public function getCusPaymentTotal(){
        return $this->cus_payment_total;
    }

    /**
     * Set cus_shipping_status
     * 
     * @param int|null $cus_shipping_status
     * 
     * @return CusShipping
     */
    public function setCusShippingStatus($cus_shipping_status=null){
        $this->cus_shipping_status = $cus_shipping_status;
        return $this;
    }

      /**
     * Get cus_shipping_status
     * 
     * @return int|null
     * 
     * 
     */
    public function getCusShippingStatus(){
        return $this->cus_shipping_status;
    }


}  

