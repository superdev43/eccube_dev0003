<?php

namespace Customize\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Customize\Entity\WeekHoliday')) {

}
/**
 * Holiday
 *
 * @ORM\Table(name="dtb_week_holiday")

 * @ORM\Entity(repositoryClass="Customize\Repository\WeekHolidayRepository")
 */

class WeekHoliday extends \Eccube\Entity\AbstractEntity
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
     * @var int
     *
     * @ORM\Column(name="yobi_id", type="integer")
     * 
     */
    private $yobi_id;

    /**
     * @var string
     * 
     * @ORM\Column(name="day", type="string")
     */
    private $day;

    /**
     * @var integer
     * 
     * @ORM\Column(name="status", type="integer")
     */
    private $status;

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
     * Get yobi_id.
     *
     * @return int|null
     */
    public function getYobiId()
    {
        return $this->yobi_id;
    }

    /**
     * Set yobi_id
     * 
     * @param int|null $yobi_id
     * 
     * @return WeekHoliday
     * 
     */
    public function setYobiId($yobi_id = null)
    {
        $this->yobi_id = $yobi_id;
        return $this;
    }

    /**
     * Set day
     * 
     * @param string|null $day
     * 
     * @return WeekHoliday
     * 
     */
    public function setDay($day = null)
    {
        $this->day = $day;
        return $this;
    }

    /**
     * Get day
     * @return string|null
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * Set status
     * 
     * @param int|null $status
     * 
     * @return WeekHoliday
     * 
     */
    public function setStatus($status = null)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Get status
     * @return int|null
     */
    public function getStatus()
    {
        return $this->status;
    }

}  

