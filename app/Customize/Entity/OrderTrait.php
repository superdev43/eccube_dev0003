<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $delivery_method_flag;


    /**
     * Get delivery_method_flag
     * @return int
     */
    public function getDeliveryMethodFlag()
    {
        return $this->delivery_method_flag;
    }

    /**
     * Set delivery_method_flag
     * 
     * @param int $delivery_method_flag
     * 
     * @return OrderTrait
     * 
     */
    public function setDeliveryMethodFlag($delivery_method_flag = null){
        $this->delivery_method_flag = $delivery_method_flag;
        return $this; 
    }

    
}
