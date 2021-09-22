<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Eccube\Annotation as Eccube;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Eccube\FormAppend(
     *     auto_render=true,
     *     type="\Symfony\Component\Form\Extension\Core\Type\IntegerType",
     *     options={
     *          "required": false,
     *          "label": "送料"
     *     })
     */
    public $shipping_charge;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Eccube\FormAppend(
     *     auto_render=true,
     *     form_theme = "Form/no_fee.twig",
     *     options={
     *          "required": false,
     *          "label": "送料無料"
     *     })
     */
    public $no_fee;


    /**
     * @ORM\Column(type="string", nullable=true)
     * @Eccube\FormAppend(
     *     auto_render=true,
     *     form_theme = "Form/only_prem_product_display.twig",
     *     options={
     *          "required": false,
     *          "label": "商品を表⽰"
     *     })
     */
    public $only_prem_product_display;
    
    /**
     * @ORM\Column(type="string", nullable=true)
     * @Eccube\FormAppend(
     *     auto_render=true,
     *     form_theme = "Form/only_prem_price_display.twig",
     *     options={
     *          "required": false,
     *          "label": "⾦額を表⽰"
     *     })
     */
    public $only_prem_price_display;

    

    /**
     * @ORM\Column(type="integer", nullable=true)
     * 
     */
    public $cus_customer_level_product;


    /**
     * Get cus_customer_level_product
     * @return int|null
     */
    public function getCusCustomerLevelProduct()
    {
        return $this->cus_customer_level_product;
    }

    
    /**
     * Set cus_customer_level_product
     * 
     * @param int|null $cus_customer_level_product
     * 
     * @return ProductTrait
     * 
     */
    public function setCusCustomerLevelProduct($cus_customer_level_product = null)
    {
        $this->cus_customer_level_product = $cus_customer_level_product;
        return $this;
    }
    /**
     * Get shipping_charge
     * @return int|null
     */
    public function getShippingCharge()
    {
        return $this->shipping_charge;
    }

    
    /**
     * Set shipping_charge
     * 
     * @param int|null $shipping_charge
     * 
     * @return ProductTrait
     * 
     */
    public function setShippingCharge($shipping_charge = null)
    {
        $this->shipping_charge = $shipping_charge;
        return $this;
    }

    /**
     * Get no_fee
     * @return int|null
     */
    public function getNoFee()
    {
        return $this->no_fee;
    }

    
    /**
     * Set no_fee
     * 
     * @param int|null $no_fee
     * 
     * @return ProductTrait
     * 
     */
    public function setNoFee($no_fee = null)
    {
        $this->no_fee = $no_fee;
        return $this;
    }

    /**
     * Get only_prem_price_display
     * @return int|null
     */
    public function getOnlyPremPriceDisplay()
    {
        return $this->only_prem_price_display;
    }

    
    /**
     * Set only_prem_price_display
     * 
     * @param int|null $only_prem_price_display
     * 
     * @return ProductTrait
     * 
     */
    public function setOnlyPremPriceDisplay($only_prem_price_display = null)
    {
        $this->only_prem_price_display = $only_prem_price_display;
        return $this;
    }

    /**
     * Get only_prem_product_display
     * @return int|null
     */
    public function getOnlyPremProductDisplay()
    {
        return $this->only_prem_product_display;
    }

    
    /**
     * Set only_prem_product_display
     * 
     * @param int|null $only_prem_product_display
     * 
     * @return ProductTrait
     * 
     */
    public function setOnlyPremProductDisplay($only_prem_product_display = null)
    {
        $this->only_prem_product_display = $only_prem_product_display;
        return $this;
    }

}