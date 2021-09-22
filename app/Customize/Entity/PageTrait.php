<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Eccube\Annotation as Eccube;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @EntityExtension("Eccube\Entity\Page")
 */
trait PageTrait
{

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Eccube\FormAppend(
     *     auto_render=true,
     *     form_theme = "Form/is_prem_member.twig",
     *     options={
     *          "required": false,
     *          "label": "有料会員のみ"
     *     })
     */
    public $is_prem_member;

    /**
     * Get is_prem_member
     * @return int|null
     */
    public function getIsPremMember()
    {
        return $this->is_prem_member;
    }

    
    /**
     * Set is_prem_member
     * 
     * @param int|null $is_prem_member
     * 
     * @return PageTrait
     * 
     */
    public function setIsPremMember($is_prem_member = null)
    {
        $this->is_prem_member = $is_prem_member;
        return $this;
    }

}