<?php

namespace Customize\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Customize\Entity\Holiday')) {

}
/**
 * Holiday
 *
 * @ORM\Table(name="dtb_holiday")

 * @ORM\Entity(repositoryClass="Customize\Repository\HolidayRepository")
 */

class Holiday extends \Eccube\Entity\AbstractEntity
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
     * @var string
     * 
     * @ORM\Column(name="year", type="string")
     */
    private $year;

    /**
     * @var string
     * 
     * @ORM\Column(name="month", type="string")
     */
    private $month;

    /**
     * @var string
     * 
     * @ORM\Column(name="day", type="string")
     */
    private $day;

    /**
     * @var integer
     * 
     * @ORM\Column(name="del_flag", type="integer")
     */
    private $del_flag;

    /**
     * @var string
     * 
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;



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
     * Set year
     * 
     * @param string|null $year
     * 
     * @return Holiday
     * 
     */
    public function setYear($year = null)
    {
        $this->year = $year;
        return $this;
    }

    /**
     * Get year
     * @return string|null
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set month
     * 
     * @param string|null $month
     * 
     * @return Holiday
     * 
     */
    public function setMonth($month = null)
    {
        $this->month = $month;
        return $this;
    }

    /**
     * Get month
     * @return string|null
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * Set day
     * 
     * @param string|null $day
     * 
     * @return Holiday
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
     * Set del_flag
     * 
     * @param int|null $del_flag
     * 
     * @return Holiday
     * 
     */
    public function setDelFlag($del_flag = null)
    {
        $this->del_flag = $del_flag;
        return $this;
    }

    /**
     * Get del_flag
     * @return int|null
     */
    public function getDelFlag()
    {
        return $this->del_flag;
    }


    /**
     * Set title
     * 
     * @param string|null $title
     * 
     * @return Holiday
     */
    public function setTitle($title = null)
    {
        $this->title = $title;
        return $this;
    }


    /**
     * Get title
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

}  

