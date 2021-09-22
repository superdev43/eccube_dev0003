<?php

namespace Plugin\CustomShipping\Entity;

use Doctrine\ORM\Mapping as ORM;

if (!class_exists('Plugin\CustomShipping\Entity\Config')) {
    /**
     * Config
     *
     * @ORM\Table(name="dtb_shi")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Customize\Repository\OptionRepository")
     */
    class Config extends \Eccube\Entity\AbstractEntity
    {

        /**
         * @var int
         *
         * @ORM\Column(name="id", type="integer", options={"unsigned":true})
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;

        /**
         * @var string
         *
         * @ORM\Column(name="name", type="string", length=255)
         */
        private $name;

        /**
         * @var int
         *
         * @ORM\Column(name="price", type="integer")
         */
        private $price;


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
         * Get name
         *
         * @return name
         */
        public function getName()
        {
            return $this->name;
        }

        /**
         * Set name
         *
         * @return this
         */
        public function setName($name)
        {
            $this->name = $name;
        }

        /**
         * Get price
         *
         * @return price
         */
        public function getPrice()
        {
            return $this->price;
        }

        /**
         * Set price
         *
         * @return this
         */
        public function setPrice($price)
        {
            $this->price = $price;
        }
    }
}
