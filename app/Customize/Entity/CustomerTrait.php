<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Eccube\Annotation as Eccube;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @EntityExtension("Eccube\Entity\Customer")
 */
trait CustomerTrait
{
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $cus_customer_level;

    /**
     * @ORM\Column(type="integer", name="recurring_amount")
     */
    public $recurring_amount;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="recurring_start_datetime", type="datetimetz")
     */    
    private $recurring_start_datetime;

    /**
     * @ORM\Column(type="integer", name="last_time_recurring_status")
     */
    public $last_time_recurring_status;

     /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_time_recurring_datetime", type="datetimetz")
     */
    private $last_time_recurring_datetime;

     /**
     * @var string
     *
     * @ORM\Column(name="recurring_method", type="string")
     */
    private $recurring_method;




    /**
     * Get cus_customer_level
     * @return int|null
     */
    public function getCusCustomerLevel()
    {
        return $this->cus_customer_level;
    }

    
    /**
     * Set cus_customer_level
     * 
     * @param int|null $cus_customer_level
     * 
     * @return CustomerTrait
     * 
     */
    public function setCusCustomerLevel($cus_customer_level = null)
    {
        $this->cus_customer_level = $cus_customer_level;
        return $this;
    }

    /**
     * Get recurring_amount
     * @return int|null
     */
    public function getRecurringAmount()
    {
        return $this->recurring_amount;
    }

    
    /**
     * Set recurring_amount
     * 
     * @param int|null $recurring_amount
     * 
     * @return CustomerTrait
     * 
     */
    public function setRecurringAmount($recurring_amount = null)
    {
        $this->recurring_amount = $recurring_amount;
        return $this;
    }

    /**
     * Get last_time_recurring_status
     * @return int|null
     */
    public function getLastTimeRecurringStatus()
    {
        return $this->last_time_recurring_status;
    }

    
    /**
     * Set last_time_recurring_status
     * 
     * @param int|null $last_time_recurring_status
     * 
     * @return CustomerTrait
     * 
     */
    public function setLastTimeRecurringStatus($last_time_recurring_status = null)
    {
        $this->last_time_recurring_status = $last_time_recurring_status;
        return $this;
    }

    /**
     * Set recurring_start_datetime.
     *
     * @param \DateTime $recurring_start_datetime
     *
     * @return Customer
     */
    public function setRecurringStartDatetime($recurring_start_datetime)
    {
        $this->recurring_start_datetime = $recurring_start_datetime;

        return $this;
    }

    /**
     * Get last_time_recurring_datetime.
     *
     * @return \DateTime
     */
    public function getLastTimeRecurringDatetime()
    {
        return $this->last_time_recurring_datetime;
    }

    /**
     * Set last_time_recurring_datetime.
     *
     * @param \DateTime $last_time_recurring_datetime
     *
     * @return Customer
     */
    public function setLastTimeRecurringDatetime($last_time_recurring_datetime)
    {
        $this->last_time_recurring_datetime = $last_time_recurring_datetime;

        return $this;
    }

    /**
     * Get recurring_start_datetime.
     *
     * @return \DateTime
     */
    public function getRecurringStartDatetime()
    {
        return $this->recurring_start_datetime;
    }

    /**
     * Get recurring_method
     * @return string|null
     */
    public function getRecurringMethod()
    {
        return $this->recurring_method;
    }

    
    /**
     * Set recurring_method
     * 
     * @param string|null $recurring_method
     * 
     * @return CustomerTrait
     * 
     */
    public function setRecurringMethod($recurring_method = null)
    {
        $this->recurring_method = $recurring_method;
        return $this;
    }

}