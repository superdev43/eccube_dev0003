<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Service\PurchaseFlow;

use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\ItemInterface;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Delivery;
use Eccube\Entity\DeliveryFee;
use Eccube\Repository\DeliveryRepository;
use Eccube\Repository\DeliveryFeeRepository;
use Doctrine\ORM\EntityManager;

use function PHPSTORM_META\type;

class PurchaseFlow
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var DeliveryRepository
     */
    protected $deliveryRepository;
    /**
     * @var DeliveryFeeRepository
     */
    protected $deliveryFeeRepository;
    /**
     * @var string
     */
    protected $flowType;

    /**
     * @var ArrayCollection|ItemPreprocessor[]
     */
    protected $itemPreprocessors;

    /**
     * @var ArrayCollection|ItemHolderPreprocessor[]
     */
    protected $itemHolderPreprocessors;

    /**
     * @var ArrayCollection|ItemValidator[]
     */
    protected $itemValidators;

    /**
     * @var ArrayCollection|ItemHolderValidator[]
     */
    protected $itemHolderValidators;

    /**
     * @var ArrayCollection|ItemHolderPostValidator[]
     */
    protected $itemHolderPostValidators;

    /**
     * @var ArrayCollection|DiscountProcessor[]
     */
    protected $discountProcessors;

    /**
     * @var ArrayCollection|PurchaseProcessor[]
     */
    protected $purchaseProcessors;

    public function __construct()
    {
        $this->purchaseProcessors = new ArrayCollection();
        $this->itemValidators = new ArrayCollection();
        $this->itemHolderValidators = new ArrayCollection();
        $this->itemPreprocessors = new ArrayCollection();
        $this->itemHolderPreprocessors = new ArrayCollection();
        $this->itemHolderPostValidators = new ArrayCollection();
        $this->discountProcessors = new ArrayCollection();
        $app = \Eccube\Application::getInstance();
        $this->em = $app['orm.em'];
        $this->deliveryRepository = $this->em->getRepository(Delivery::class);
        $this->deliveryFeeRepository = $this->em->getRepository(DeliveryFee::class);
    }

    public function setFlowType($flowType)
    {
        $this->flowType = $flowType;
    }

    public function setPurchaseProcessors(ArrayCollection $processors)
    {
        $this->purchaseProcessors = $processors;
    }

    public function setItemValidators(ArrayCollection $itemValidators)
    {
        $this->itemValidators = $itemValidators;
    }

    public function setItemHolderValidators(ArrayCollection $itemHolderValidators)
    {
        $this->itemHolderValidators = $itemHolderValidators;
    }

    public function setItemPreprocessors(ArrayCollection $itemPreprocessors)
    {
        $this->itemPreprocessors = $itemPreprocessors;
    }

    public function setItemHolderPreprocessors(ArrayCollection $itemHolderPreprocessors)
    {
        $this->itemHolderPreprocessors = $itemHolderPreprocessors;
    }

    public function setItemHolderPostValidators(ArrayCollection $itemHolderPostValidators)
    {
        $this->itemHolderPostValidators = $itemHolderPostValidators;
    }

    public function setDiscountProcessors(ArrayCollection $discountProcessors)
    {
        $this->discountProcessors = $discountProcessors;
    }

    public function validate(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        $context->setFlowType($this->flowType);

        $this->calculateAll($itemHolder);

        $flowResult = new PurchaseFlowResult($itemHolder);

        foreach ($itemHolder->getItems() as $item) {
            foreach ($this->itemValidators as $itemValidator) {
                $result = $itemValidator->execute($item, $context);
                $flowResult->addProcessResult($result);
            }
        }

        $this->calculateAll($itemHolder);

        foreach ($this->itemHolderValidators as $itemHolderValidator) {
            $result = $itemHolderValidator->execute($itemHolder, $context);
            $flowResult->addProcessResult($result);
        }

        $this->calculateAll($itemHolder);

        foreach ($itemHolder->getItems() as $item) {
            foreach ($this->itemPreprocessors as $itemPreprocessor) {
                $itemPreprocessor->process($item, $context);
            }
        }

        $this->calculateAll($itemHolder);

        foreach ($this->itemHolderPreprocessors as $holderPreprocessor) {
            $result = $holderPreprocessor->process($itemHolder, $context);
            if ($result) {
                $flowResult->addProcessResult($result);
            }

            $this->calculateAll($itemHolder);
        }

        foreach ($this->discountProcessors as $discountProcessor) {
            $discountProcessor->removeDiscountItem($itemHolder, $context);
        }

        $this->calculateAll($itemHolder);

        foreach ($this->discountProcessors as $discountProcessor) {
            $result = $discountProcessor->addDiscountItem($itemHolder, $context);
            if ($result) {
                $flowResult->addProcessResult($result);
            }
            $this->calculateAll($itemHolder);
        }

        foreach ($this->itemHolderPostValidators as $itemHolderPostValidator) {
            $result = $itemHolderPostValidator->execute($itemHolder, $context);
            $flowResult->addProcessResult($result);

            $this->calculateAll($itemHolder);
        }

        return $flowResult;
    }

    /**
     * ??????????????????????????????.
     *
     * @param ItemHolderInterface $target
     * @param PurchaseContext $context
     *
     * @throws PurchaseException
     */
    public function prepare(ItemHolderInterface $target, PurchaseContext $context)
    {
        $context->setFlowType($this->flowType);

        foreach ($this->purchaseProcessors as $processor) {
            $processor->prepare($target, $context);
        }
    }

    /**
     * ???????????????????????????.
     *
     * @param ItemHolderInterface $target
     * @param PurchaseContext $context
     *
     * @throws PurchaseException
     */
    public function commit(ItemHolderInterface $target, PurchaseContext $context)
    {
        $context->setFlowType($this->flowType);

        foreach ($this->purchaseProcessors as $processor) {
            $processor->commit($target, $context);
        }
    }

    /**
     * ??????????????????????????????????????????.
     *
     * @param ItemHolderInterface $target
     * @param PurchaseContext $context
     */
    public function rollback(ItemHolderInterface $target, PurchaseContext $context)
    {
        $context->setFlowType($this->flowType);

        foreach ($this->purchaseProcessors as $processor) {
            $processor->rollback($target, $context);
        }
    }

    public function addPurchaseProcessor(PurchaseProcessor $processor)
    {
        $this->purchaseProcessors[] = $processor;
    }

    public function addItemHolderPreprocessor(ItemHolderPreprocessor $holderPreprocessor)
    {
        $this->itemHolderPreprocessors[] = $holderPreprocessor;
    }

    public function addItemPreprocessor(ItemPreprocessor $itemPreprocessor)
    {
        $this->itemPreprocessors[] = $itemPreprocessor;
    }

    public function addItemValidator(ItemValidator $itemValidator)
    {
        $this->itemValidators[] = $itemValidator;
    }

    public function addItemHolderValidator(ItemHolderValidator $itemHolderValidator)
    {
        $this->itemHolderValidators[] = $itemHolderValidator;
    }

    public function addItemHolderPostValidator(ItemHolderPostValidator $itemHolderValidator)
    {
        $this->itemHolderPostValidators[] = $itemHolderValidator;
    }

    public function addDiscountProcessor(DiscountProcessor $discountProcessor)
    {
        $this->discountProcessors[] = $discountProcessor;
    }

    /**
     * @param ItemHolderInterface $itemHolder
     */
    protected function calculateTotal(ItemHolderInterface $itemHolder)
    {
        $shipping_charge_sum = 0;
        $withShippingCharge = 0;
        $discount_sum = 0;
        if (!$itemHolder instanceof Order) {
            $shippingChargeCartItems = $itemHolder->getItems();

            // foreach ($shippingChargeCartItems as $chargeItem) {
                
            //     $shipping_charge_sum += $chargeItem->getProductClass()->getProduct()->shipping_charge * $chargeItem->getQuantity();
            // }
            $total = array_reduce($itemHolder->getItems()->toArray(), 
            function ($sum, ItemInterface $item) {
                $sum += $item->getPriceIncTax() * $item->getQuantity();
                return $sum;
            }
            , $shipping_charge_sum);
        } else {
            $shippingChargeOrderItems = $itemHolder->getProductOrderItems();
            $discountItems = $itemHolder->getTaxFreeDiscountItems();
            foreach($discountItems as $item){
                $discount_sum += $item->getTotalPrice();
            }

            foreach ($shippingChargeOrderItems as $chargeItem) {
                if( $chargeItem->getProductClass()->getProduct()->no_fee != 1){

                    if( $chargeItem->getProductClass()->getProduct()->shipping_charge == Null){
                        $sale_type_id =  $chargeItem->getProductClass()->getSaleType()->getId();
                        $pref_id =  $chargeItem->getOrder()->getPref()->getId();

                        $delivery_id = 1;
                        if($chargeItem->getOrder()->getDeliveryMethodFlag() != null){
                            $delivery_id = $chargeItem->getOrder()->getDeliveryMethodFlag();
                        }
                        // var_export($delivery_id);die;

                        // $delivery_id= $this->deliveryRepository->findOneBy(['SaleType'=>$sale_type_id]);
                        $delivery_fee = $this->deliveryFeeRepository->findOneBy([
                            'Delivery'=>$delivery_id,
                            'Pref'=>$pref_id
                        ])->getFee();
                        $withShippingCharge = $delivery_fee;
                    }
                    else{
        
                        $shipping_charge_sum += $chargeItem->getProductClass()->getProduct()->shipping_charge * $chargeItem->getQuantity();
                    }
                }
               
            }
            $shipping_charge_sum += $withShippingCharge;
            $total = array_reduce($itemHolder->getProductOrderItems(), 
            function ($sum, ItemInterface $item) {
                $sum += $item->getPriceIncTax() * $item->getQuantity();
                return $sum;
            }
            , $shipping_charge_sum + $discount_sum);
        }
        
        $itemHolder->setTotal($total);
        // TODO
        if ($itemHolder instanceof Order) {
            // Order ?????? PaymentTotal ??????????????????
            $itemHolder->setPaymentTotal($total);
        }
    }

    protected function calculateSubTotal(ItemHolderInterface $itemHolder)
    {
        $total = $itemHolder->getItems()
            ->getProductClasses()
            ->reduce(function ($sum, ItemInterface $item) {
                $sum += $item->getPriceIncTax() * $item->getQuantity();

                return $sum;
            }, 0);
        // TODO
        if ($itemHolder instanceof Order) {
            // Order ???????????? SubTotal ??????????????????
            $itemHolder->setSubTotal($total);
        }
    }

    /**
     * @param ItemHolderInterface $itemHolder
     */
    protected function calculateDeliveryFeeTotal(ItemHolderInterface $itemHolder)
    {

        $shipping_charge_sum = 0;
        $withShippingCharge = 0;
        if (!$itemHolder instanceof Order) {

            $shippingChargeCartItems = $itemHolder->getItems();
            // foreach ($shippingChargeCartItems as $chargeItem) {
                
            //     $shipping_charge_sum += $chargeItem->getProductClass()->getProduct()->shipping_charge * $chargeItem->getQuantity();
            // }
        }
        else {
            $shippingChargeOrderItems = $itemHolder->getProductOrderItems();

            foreach ($shippingChargeOrderItems as $chargeItem) {
                if( $chargeItem->getProductClass()->getProduct()->no_fee != 1){

                    if( $chargeItem->getProductClass()->getProduct()->shipping_charge == Null){
                        $sale_type_id =  $chargeItem->getProductClass()->getSaleType()->getId();
                        $pref_id =  $chargeItem->getOrder()->getPref()->getId();
                        // $delivery_id= $this->deliveryRepository->findOneBy(['SaleType'=>$sale_type_id]);
                        $delivery_id = 1;
                        if($chargeItem->getOrder()->getDeliveryMethodFlag() != null){
                            $delivery_id = $chargeItem->getOrder()->getDeliveryMethodFlag();
                        }
                        $delivery_fee = $this->deliveryFeeRepository->findOneBy([
                            'Delivery'=>$delivery_id,
                            'Pref'=>$pref_id
                        ])->getFee();
                        $withShippingCharge = $delivery_fee;
                    }
                    else{
        
                        $shipping_charge_sum += $chargeItem->getProductClass()->getProduct()->shipping_charge * $chargeItem->getQuantity();
                    }
                }
            }
        }
        $shipping_charge_sum += $withShippingCharge;

        $total = $itemHolder->getItems()
            ->getDeliveryFees()
            ->reduce(function ($sum, ItemInterface $item) {
                
                return $sum;
            }, $shipping_charge_sum);
        $itemHolder->setDeliveryFeeTotal($total);
    }

    /**
     * @param ItemHolderInterface $itemHolder
     */
    protected function calculateDiscount(ItemHolderInterface $itemHolder)
    {
        $total = $itemHolder->getItems()
            ->getDiscounts()
            ->reduce(function ($sum, ItemInterface $item) {
                $sum += $item->getPriceIncTax() * $item->getQuantity();

                return $sum;
            }, 0);
        // TODO ????????????????????? discount ?????????????????????????????????
        $itemHolder->setDiscount($total * -1);
    }

    /**
     * @param ItemHolderInterface $itemHolder
     */
    protected function calculateCharge(ItemHolderInterface $itemHolder)
    {
        $total = $itemHolder->getItems()
            ->getCharges()
            ->reduce(function ($sum, ItemInterface $item) {
                $sum += $item->getPriceIncTax() * $item->getQuantity();

                return $sum;
            }, 0);
        $itemHolder->setCharge($total);
    }

    /**
     * @param ItemHolderInterface $itemHolder
     */
    protected function calculateTax(ItemHolderInterface $itemHolder)
    {
        $total = $itemHolder->getItems()
            ->reduce(function ($sum, ItemInterface $item) {
                if ($item instanceof OrderItem) {
                    $sum += $item->getTax() * $item->getQuantity();
                } else {
                    $sum += ($item->getPriceIncTax() - $item->getPrice()) * $item->getQuantity();
                }

                return $sum;
            }, 0);
        $itemHolder->setTax($total);
    }

    /**
     * @param ItemHolderInterface $itemHolder
     */
    protected function calculateAll(ItemHolderInterface $itemHolder)
    {
        $this->calculateDeliveryFeeTotal($itemHolder);
        $this->calculateCharge($itemHolder);
        $this->calculateDiscount($itemHolder);
        $this->calculateSubTotal($itemHolder); // Order ???????????????
        $this->calculateTax($itemHolder);
        $this->calculateTotal($itemHolder);
    }

    /**
     * PurchaseFlow ???????????????????????????.
     *
     * @return string
     */
    public function dump()
    {
        $callback = function ($processor) {
            return get_class($processor);
        };
        $flows = [
            0 => $this->flowType . ' flow',
            'ItemValidator' => $this->itemValidators->map($callback)->toArray(),
            'ItemHolderValidator' => $this->itemHolderValidators->map($callback)->toArray(),
            'ItemPreprocessor' => $this->itemPreprocessors->map($callback)->toArray(),
            'ItemHolderPreprocessor' => $this->itemHolderPreprocessors->map($callback)->toArray(),
            'DiscountProcessor' => $this->discountProcessors->map($callback)->toArray(),
            'ItemHolderPostValidator' => $this->itemHolderPostValidators->map($callback)->toArray()
        ];
        $tree  = new \RecursiveTreeIterator(new \RecursiveArrayIterator($flows));
        $tree->setPrefixPart(\RecursiveTreeIterator::PREFIX_RIGHT, ' ');
        $tree->setPrefixPart(\RecursiveTreeIterator::PREFIX_MID_LAST, '???');
        $tree->setPrefixPart(\RecursiveTreeIterator::PREFIX_MID_HAS_NEXT, '???');
        $tree->setPrefixPart(\RecursiveTreeIterator::PREFIX_END_HAS_NEXT, '???');
        $tree->setPrefixPart(\RecursiveTreeIterator::PREFIX_END_LAST, '???');
        $out = '';
        foreach ($tree as $key => $value) {
            if (is_numeric($key)) {
                $out .= $value . PHP_EOL;
            } else {
                $out .= $key . PHP_EOL;
            }
        }
        return $out;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->dump();
    }
}
