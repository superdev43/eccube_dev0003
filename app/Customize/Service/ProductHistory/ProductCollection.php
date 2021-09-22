<?php
/**
 * This file is part of ProductHistory
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 * https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Service\ProductHistory;


use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Entity\Product;

/**
 * Class ProductCollection
 * @package Customize\Service\CheckedProduct
 */
class ProductCollection extends ArrayCollection
{
    /**
     * @var int
     */
    private $limit;

    /**
     * ProductCollection constructor.
     * @param array $cookie
     * @param int $limit
     */
    public function __construct(array $cookie = [], int $limit = 12)
    {
        $this->limit = $limit;
        parent::__construct($cookie);
    }

    /**
     * @param Product $product
     * @return bool|true
     */
    public function addProduct(Product $product): bool
    {
        // array_reverse($this->getValues(), true);
        if ($this->count() > $this->limit) {
            $this->removeElement($this->last());
        }

        if($this->contains($product->getId())) {
            return false;
        }

        return parent::add($product->getId());
    }
}