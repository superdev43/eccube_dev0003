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

namespace Eccube\Service;

use DateTime;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Csv;
use Eccube\Entity\CusCsv;
use Eccube\Entity\Master\CsvType;
use Eccube\Form\Type\Admin\SearchCustomerType;
use Eccube\Form\Type\Admin\SearchOrderType;
use Eccube\Form\Type\Admin\SearchProductType;
use Eccube\Repository\CsvRepository;
use Eccube\Repository\CusCsvRepository;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\CsvTypeRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\ShippingRepository;
use Eccube\Util\EntityUtil;
use Eccube\Util\FormUtil;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Eccube\Repository\OrderItemRepository;
use Plugin\CustomShipping\Repository\CusShippingRepository;


class CsvExportService
{
    /**
     * @var resource
     */
    protected $fp;

    /**
     * @var boolean
     */
    protected $closed = false;

    /**
     * @var \Closure
     */
    protected $convertEncodingCallBack;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var QueryBuilder;
     */
    protected $qb;

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var CsvType
     */
    protected $CsvType;

    /**
     * @var Csv[]
     */
    protected $Csvs;

    /**
     * @var CusCsv[]
     */
    protected $CusCsvs;

    /**
     * @var CsvRepository
     */
    protected $csvRepository;

    /**
     * @var CusShippingRepository
     */
    protected $cusShippingRepository;

    /**
     * @var CusCsvRepository
     */
    protected $cusCsvRepository;

    /**
     * @var CsvTypeRepository
     */
    protected $csvTypeRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderItemRepository
     */
    protected $orderItemRepository;

    /**
     * @var ShippingRepository
     */
    protected $shippingRepository;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * CsvExportService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param CsvRepository $csvRepository
     * @param CsvTypeRepository $csvTypeRepository
     * @param OrderRepository $orderRepository
     * @param CustomerRepository $customerRepository
     * @param EccubeConfig $eccubeConfig
     * @param CusCsvRepository $cusCsvRepository
     * @param CusCsv $CusCsvs
     * @param OrderItemRepository $orderItemRepository
     * @param CusShipping @cusShipping
     * @param CusShippingRepository $cusShippingRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        CsvRepository $csvRepository,
        CsvTypeRepository $csvTypeRepository,
        OrderRepository $orderRepository,
        ShippingRepository $shippingRepository,
        CustomerRepository $customerRepository,
        ProductRepository $productRepository,
        EccubeConfig $eccubeConfig,
        FormFactoryInterface $formFactory,
        CusCsv $CusCsvs,
        CusCsvRepository $cusCsvRepository,
        OrderItemRepository $orderItemRepository,
        CusShippingRepository $cusShippingRepository
    ) {
        $this->entityManager = $entityManager;
        $this->csvRepository = $csvRepository;
        $this->csvTypeRepository = $csvTypeRepository;
        $this->orderRepository = $orderRepository;
        $this->shippingRepository = $shippingRepository;
        $this->customerRepository = $customerRepository;
        $this->eccubeConfig = $eccubeConfig;
        $this->productRepository = $productRepository;
        $this->formFactory = $formFactory;
        $this->CusCsvs = $CusCsvs;
        $this->cusCsvRepository = $cusCsvRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->cusShippingRepository = $cusShippingRepository;
    }

    /**
     * @param $config
     */
    public function setConfig($config)
    {
        $this->eccubeConfig = $config;
    }

    /**
     * @param CsvRepository $csvRepository
     */
    public function setCsvRepository(CsvRepository $csvRepository)
    {
        $this->csvRepository = $csvRepository;
    }

    /**
     * @param CusCsvRepository $cusCsvRepository
     */
    public function setCusCsvRepository(CusCsvRepository $cusCsvRepository)
    {
        $this->cusCsvRepository = $cusCsvRepository;
    }

    /**
     * @param CsvTypeRepository $csvTypeRepository
     */
    public function setCsvTypeRepository(CsvTypeRepository $csvTypeRepository)
    {
        $this->csvTypeRepository = $csvTypeRepository;
    }

    /**
     * @param OrderRepository $orderRepository
     */
    public function setOrderRepository(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param CustomerRepository $customerRepository
     */
    public function setCustomerRepository(CustomerRepository $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * @param ProductRepository $productRepository
     */
    public function setProductRepository(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function setEntityManager(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @param QueryBuilder $qb
     */
    public function setExportQueryBuilder(QueryBuilder $qb)
    {
        $this->qb = $qb;
    }

    /**
     * Csv????????????Service?????????????????????.
     *
     * @param $CsvType|integer
     */
    public function initCsvType($CsvType)
    {
        if ($CsvType instanceof CsvType) {
            $this->CsvType = $CsvType;
        } else {
            $this->CsvType = $this->csvTypeRepository->find($CsvType);
        }

        $criteria = [
            'CsvType' => $CsvType,
            'enabled' => true,
        ];
        $orderBy = [
            'sort_no' => 'ASC',
        ];
        $this->Csvs = $this->csvRepository->findBy($criteria, $orderBy);
    }

    /**
     * @return Csv[]
     */
    public function getCsvs()
    {
        return $this->Csvs;
    }

    /**
     * ???????????????????????????.
     * ????????????????????????????????????, ?????????initCsvType($CsvType)???????????????????????????????????????.
     */
    public function exportHeader()
    {
        if (is_null($this->CsvType) || is_null($this->Csvs)) {
            throw new \LogicException('init csv type incomplete.');
        }
        $row = [];
        foreach ($this->Csvs as $Csv) {
            $row[] = $Csv->getDispName();
        }

        $this->fopen();
        $this->fputcsv($row);
        $this->fclose();
    }

    /**
     * @return CusCsv[]
     */
    public function getCusCsvs()
    {
        return $this->CusCsvs;
    }

    /**
     * ???????????????????????????.
     * ????????????????????????????????????, ?????????initCsvType($CsvType)???????????????????????????????????????.
     */
    public function exportHeader_cus()
    {

        $row = ['?????????(????????????)', '?????????', '??????', '??????', '??????', '??????', '', '?????????', '', '', '???????????????', '?????????', '??????', '??????', '????????????????????????', '??????Id'];

        $this->fopen();
        $this->fputcsv($row);
        $this->fclose();
    }
    /**
     * ???????????????????????????. order_page
     * ????????????????????????????????????, ?????????initCsvType($CsvType)???????????????????????????????????????.
     */
    public function exportHeader_cus_order_page()
    {

        $row = ['?????????(????????????)', '?????????', '??????', '??????', '??????', '??????', '', '?????????', '', '', '???????????????', '?????????', '??????', '??????', '????????????????????????'];

        $this->fopen();
        $this->fputcsv($row);
        $this->fclose();
    }

    /**
     * ???????????????????????????????????????????????????????????????.
     * ????????????????????????????????????, ?????????setExportQueryBuilder($qb)????????????????????????????????????????????????????????????????????????.
     *
     * @param \Closure $closure
     */
    public function exportData(\Closure $closure)
    {
        if (is_null($this->qb) || is_null($this->entityManager)) {
            throw new \LogicException('query builder not set.');
        }

        $this->fopen();

        $query = $this->qb->getQuery();
        foreach ($query->getResult() as $iterableResult) {
            $closure($iterableResult, $this);
            $this->entityManager->detach($iterableResult);
            $query->free();
            flush();
        }

        $this->fclose();
    }

    /**
     * @param Request $request
     * 
     * @param array $shi_ids
     */
    public function exportData_cus(Request $request, array $shi_ids)
    {
        if (is_null($this->qb) || is_null($this->entityManager)) {
            throw new \LogicException('query builder not set.');
        }
        $this->fopen();
        $OrderItemGroup = [];
        $data_rows = [];
        for ($itemId = 0; $itemId < count($shi_ids); $itemId++) {
            $numberOfBoxes = 0;
            $OrderItemGroup[$itemId] = $this->orderItemRepository->findBy(['cus_shipping_id' => $shi_ids[$itemId]]);
            $OrderItem_sh = $OrderItemGroup[$itemId][0];

            $Order = $this->orderRepository->find($OrderItem_sh->getOrder()->getId());
            $OrderId = $Order->getId();
            $Charge = $Order->getCharge();

            $cusShippingItems = $this->cusShippingRepository->findBy([
                'cus_order_id' => $OrderId
            ]);
            $cusShippingIds = [];
            foreach ($cusShippingItems as $item) {
                $cusShippingIds[] = $item->getId();
            }
            $minCusShippingId = min($cusShippingIds);

            $ProductNameList = [];
            
            $price_p = 0;
            $flag = 0;
            foreach ($OrderItemGroup[$itemId] as $OrderItem) {
                array_push($ProductNameList, $OrderItem->getProductName());
                if ($OrderItem->getSynDeliveryFeeTotal() != null) {
                    if($OrderItem->getProductClass()->getProduct()->shipping_charge != null){
                        $price_p += ($OrderItem->getPrice() + $OrderItem->getTax()) * $OrderItem->getQuantity() + $OrderItem->getSynDeliveryFeeTotal();
                    }else{
                
                        if ($flag == 0) {
                
                            $price_p += ($OrderItem->getPrice() + $OrderItem->getTax()) * $OrderItem->getQuantity() + $OrderItem->getSynDeliveryFeeTotal();
                            $flag = 1;
                        } else {
                            $price_p += ($OrderItem->getPrice() + $OrderItem->getTax()) * $OrderItem->getQuantity();
                        }
                    }
                } else {
                    $price_p += ($OrderItem->getPrice() + $OrderItem->getTax()) * $OrderItem->getQuantity();
                }
            }
            $price = "";
            if ($OrderItem_sh->getOrder()->getPaymentMethod() == '????????????') {
                if ($minCusShippingId == $shi_ids[$itemId]) {
                    $price = $price_p + $Charge;
                } else {
            
                    $price = $price_p;
                }
            }
            $ProductName = join("/", $ProductNameList);
            foreach ($OrderItemGroup[$itemId] as $item) {

                if ($item->getProductClass()->getProduct()->no_fee != 1) {
                    if ($item->getProductClass()->getProduct()->shipping_charge != Null) {
                        $numberOfBoxes += $item->getQuantity();
                    }
                }
            }
            if ($numberOfBoxes == 0) {
                $numberOfBoxes = 1;
            }

            $data_rows[] = [$OrderItem_sh->getOrder()->getId(), $ProductName, $numberOfBoxes, $OrderItem_sh->getOrder()->getPref() . " " . $OrderItem_sh->getOrder()->getAddr01() . " " . $OrderItem_sh->getOrder()->getAddr02(), $OrderItem_sh->getOrder()->getPhoneNumber(), $OrderItem_sh->getOrder()->getName01() . $OrderItem_sh->getOrder()->getName02(), "", $price, "", "", "", "", "", "", "", $OrderItem_sh->getCusShippingId()];
        }

        foreach ($data_rows as $data_row) {

            $this->fputcsv($data_row);
        }

        $this->fclose();
    }

    /**
     * @param Request $request
     * 
     * @param array $order_ids
     */
    public function exportData_cus_order_page(Request $request, array $order_ids)
    {
        if (is_null($this->qb) || is_null($this->entityManager)) {
            throw new \LogicException('query builder not set.');
        }
        $this->fopen();
        $data_rows = [];

        foreach ($order_ids as $order_id) {
            $numberOfBoxes = 0;
            $Order = $this->orderRepository->find($order_id);
            $ProductNameList = [];
            $OrderProductItemList = $this->orderItemRepository->findBy([
                'Order' => $order_id,
                'OrderItemType' => 1
            ]);

            foreach ($OrderProductItemList as $OrderProductItem) {
                array_push($ProductNameList, $OrderProductItem->getProductName());
                if ($OrderProductItem->getProductClass()->getProduct()->no_fee != 1) {
                    if ($OrderProductItem->getProductClass()->getProduct()->shipping_charge != Null) {
                        $numberOfBoxes += $OrderProductItem->getQuantity();
                    }
                }
            }
            $ProductName = join("/", $ProductNameList);
            if ($numberOfBoxes == 0) {
                $numberOfBoxes = 1;
            }
            $price = "";
            if ($Order->getPaymentMethod() == '????????????') {
                $price = $Order->getPaymentTotal();
            }
            $data_rows[] = [$Order->getId(), $ProductName, $numberOfBoxes, $Order->getPref() . " " . $Order->getAddr01() . " " . $Order->getAddr02(), $Order->getPhoneNumber(), $Order->getName01() . $Order->getName02(), "", $price, "", "", "", "", "", "", ""];
        }

        foreach ($data_rows as $data_row) {

            $this->fputcsv($data_row);
        }

        $this->fclose();
    }

    /**
     * @param Request $request
     * 
     * @param int $orderItemId
     */
    public function exportData_cus_one(Request $request, int $orderItemId)
    {

        if (is_null($this->qb) || is_null($this->entityManager)) {
            throw new \LogicException('query builder not set.');
        }

        $OrderItem = $this->orderItemRepository->find($orderItemId);
        $numberOfBoxes = 1;
        if ($OrderItem->getProductClass()->getProduct()->no_fee != 1) {
            if ($OrderItem->getProductClass()->getProduct()->shipping_charge != Null) {
                $numberOfBoxes = $OrderItem->getQuantity();
            }
        }

        $price_p = 0;
        if ($OrderItem->getSynDeliveryFeeTotal() != null) {
            $price_p += ($OrderItem->getPrice() + $OrderItem->getTax()) * $OrderItem->getQuantity() + $OrderItem->getSynDeliveryFeeTotal();;
        } else {
            $price_p += ($OrderItem->getPrice() + $OrderItem->getTax()) * $OrderItem->getQuantity();
        }
        $price = "";
        if ($OrderItem->getOrder()->getPaymentMethod() == '????????????') {
            $price = $price_p;
        }

        // var_export($OrderItems);die;
        $this->fopen();
        $data_rows = [$OrderItem->getOrder()->getId(), $OrderItem->getProductName(), $numberOfBoxes, $OrderItem->getOrder()->getPref() . " " . $OrderItem->getOrder()->getAddr01() . " " . $OrderItem->getOrder()->getAddr02(), $OrderItem->getOrder()->getPhoneNumber(), $OrderItem->getOrder()->getName01() . $OrderItem->getOrder()->getName02(), "", $price, "", "", "", "", "", "", "", $OrderItem->getCusShippingId()];

        $this->fputcsv($data_rows);

        $this->fclose();
    }
    /**
     * @param Request $request
     * 
     * @param int $cusShippingId
     */
    public function exportData_cus_one_with_shi(Request $request, int $cusShippingId)
    {

        if (is_null($this->qb) || is_null($this->entityManager)) {
            throw new \LogicException('query builder not set.');
        }

        $OrderItems = $this->orderItemRepository->findBy(['cus_shipping_id' => $cusShippingId]);
        $OrderItem_one = $OrderItems[0];

        $Order = $this->orderRepository->find($OrderItem_one->getOrder()->getId());
        $OrderId = $Order->getId();
        $Charge = $Order->getCharge();
        $cusShippingItems = $this->cusShippingRepository->findBy([
            'cus_order_id' => $OrderId
        ]);
        $cusShippingIds = [];
        foreach ($cusShippingItems as $item) {
            $cusShippingIds[] = $item->getId();
        }

        $minCusShippingId = min($cusShippingIds);


        $ProductNameList = [];
        $price_p = 0;
        $flag = 0;
        foreach ($OrderItems as $OrderItem) {
            array_push($ProductNameList, $OrderItem->getProductName());
            if ($OrderItem->getSynDeliveryFeeTotal() != null) {
                if($OrderItem->getProductClass()->getProduct()->shipping_charge != null){
                    $price_p += ($OrderItem->getPrice() + $OrderItem->getTax()) * $OrderItem->getQuantity() + $OrderItem->getSynDeliveryFeeTotal();
                }else{

                    if ($flag == 0) {
    
                        $price_p += ($OrderItem->getPrice() + $OrderItem->getTax()) * $OrderItem->getQuantity() + $OrderItem->getSynDeliveryFeeTotal();
                        $flag = 1;
                    } else {
                        $price_p += ($OrderItem->getPrice() + $OrderItem->getTax()) * $OrderItem->getQuantity();
                    }
                }
            } else {
                $price_p += ($OrderItem->getPrice() + $OrderItem->getTax()) * $OrderItem->getQuantity();
            }
        }
        $ProductName = join("/", $ProductNameList);

        $price = "";
        if ($OrderItem_one->getOrder()->getPaymentMethod() == '????????????') {
            if ($minCusShippingId == $cusShippingId) {
                $price = $price_p + $Charge;
            } else {

                $price = $price_p;
            }
        }


        $numberOfBoxes = 0;
        foreach ($OrderItems as $item) {

            if ($item->getProductClass()->getProduct()->no_fee != 1) {
                if ($item->getProductClass()->getProduct()->shipping_charge != Null) {
                    $numberOfBoxes += $item->getQuantity();
                }
            }
        }
        if ($numberOfBoxes == 0) {
            $numberOfBoxes = 1;
        }



        // var_export($OrderItems);die;
        $this->fopen();
        $data_rows = [$OrderItem_one->getOrder()->getId(), $ProductName, $numberOfBoxes, $OrderItem_one->getOrder()->getPref() . " " . $OrderItem_one->getOrder()->getAddr01() . " " . $OrderItem_one->getOrder()->getAddr02(), $OrderItem_one->getOrder()->getPhoneNumber(), $OrderItem_one->getOrder()->getName01() . $OrderItem_one->getOrder()->getName02(), "", $price, "", "", "", "", "", "", "", $OrderItem_one->getCusShippingId()];

        $this->fputcsv($data_rows);

        $this->fclose();
    }

    /**
     * CSV????????????????????????, ??????????????????????????????.
     *
     * @param \Eccube\Entity\Csv $Csv
     * @param $entity
     *
     * @return string|null
     */
    public function getData(Csv $Csv, $entity)
    {
        // ????????????????????????????????????????????????????????????.
        $csvEntityName = str_replace('\\\\', '\\', $Csv->getEntityName());
        $entityName = ClassUtils::getClass($entity);
        if ($csvEntityName !== $entityName) {
            return null;
        }

        // ???????????????????????????????????????????????????????????????????????????.
        if (!$entity->offsetExists($Csv->getFieldName())) {
            return null;
        }

        // ??????????????????.
        $data = $entity->offsetGet($Csv->getFieldName());
        if ($Csv->getFieldName() == "price") {
            if ($entity->getOrder()->getPaymentMethod() != '????????????') {

                $data = null;
            } else {
                $data = ($entity->getPrice() + $entity->getTax()) * $entity->getQuantity();
            }
        }

        // one to one ????????????, dtb_csv.reference_field_name, ?????????????????????????????????.
        if ($data instanceof \Eccube\Entity\AbstractEntity) {
            if (EntityUtil::isNotEmpty($data)) {
                return $data->offsetGet($Csv->getReferenceFieldName());
            }
        } elseif ($data instanceof \Doctrine\Common\Collections\Collection) {
            // one to many????????????, ?????????????????????????????????.
            $array = [];
            foreach ($data as $elem) {
                if (EntityUtil::isNotEmpty($elem)) {
                    $array[] = $elem->offsetGet($Csv->getReferenceFieldName());
                }
            }

            return implode($this->eccubeConfig['eccube_csv_export_multidata_separator'], $array);
        } elseif ($data instanceof \DateTime) {
            // datetime????????????????????????????????????.
            return $data->format($this->eccubeConfig['eccube_csv_export_date_format']);
        } else {
            // ????????????????????????????????????.
            return $data;
        }

        return null;
    }

    /**
     * CSV????????????????????????, ??????????????????????????????.
     *
     * @param \Eccube\Entity\Csv $Csv
     * @param $entity
     *
     * @return string|null
     */
    public function getDataWithout(Csv $Csv, $entity)
    {
        // ????????????????????????????????????????????????????????????.
        $csvEntityName = str_replace('\\\\', '\\', $Csv->getEntityName());
        $entityName = ClassUtils::getClass($entity);
        if ($csvEntityName !== $entityName) {
            return null;
        }

        // ???????????????????????????????????????????????????????????????????????????.
        if (!$entity->offsetExists($Csv->getFieldName())) {
            return null;
        }

        // ??????????????????.
        $data = $entity->offsetGet($Csv->getFieldName());
        // if($Csv->getFieldName() == 'price' ){
        //    $data = null;
        // }

        // one to one ????????????, dtb_csv.reference_field_name, ?????????????????????????????????.
        if ($data instanceof \Eccube\Entity\AbstractEntity) {
            if (EntityUtil::isNotEmpty($data)) {
                return $data->offsetGet($Csv->getReferenceFieldName());
            }
        } elseif ($data instanceof \Doctrine\Common\Collections\Collection) {
            // one to many????????????, ?????????????????????????????????.
            $array = [];
            foreach ($data as $elem) {
                if (EntityUtil::isNotEmpty($elem)) {
                    $array[] = $elem->offsetGet($Csv->getReferenceFieldName());
                }
            }

            return implode($this->eccubeConfig['eccube_csv_export_multidata_separator'], $array);
        } elseif ($data instanceof \DateTime) {
            // datetime????????????????????????????????????.
            return $data->format($this->eccubeConfig['eccube_csv_export_date_format']);
        } else {
            // ????????????????????????????????????.
            return $data;
        }

        return null;
    }

    /**
     * ?????????????????????????????????????????????????????????????????????????????????.
     *
     * @return \Closure
     */
    public function getConvertEncodingCallback()
    {
        $config = $this->eccubeConfig;

        return function ($value) use ($config) {
            return mb_convert_encoding(
                (string) $value,
                $config['eccube_csv_export_encoding'],
                'UTF-8'
            );
        };
    }

    public function fopen()
    {
        if (is_null($this->fp) || $this->closed) {
            $this->fp = fopen('php://output', 'w');
        }
    }

    /**
     * @param $row
     */
    public function fputcsv($row)
    {
        if (is_null($this->convertEncodingCallBack)) {
            $this->convertEncodingCallBack = $this->getConvertEncodingCallback();
        }

        fputcsv($this->fp, array_map($this->convertEncodingCallBack, $row), $this->eccubeConfig['eccube_csv_export_separator']);
    }

    public function fclose()
    {
        if (!$this->closed) {
            fclose($this->fp);
            $this->closed = true;
        }
    }

    /**
     * ?????????????????????????????????????????????.
     *
     * @param Request $request
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getOrderQueryBuilder(Request $request)
    {
        $session = $request->getSession();
        $builder = $this->formFactory
            ->createBuilder(SearchOrderType::class);
        $searchForm = $builder->getForm();

        $viewData = $session->get('eccube.admin.order.search', []);
        $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

        // ?????????????????????????????????????????????.
        $qb = $this->orderRepository
            ->getQueryBuilderBySearchDataForAdmin($searchData);

        return $qb;
    }

    /**
     * ?????????????????????????????????????????????.
     *
     * @param Request $request
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getCustomerQueryBuilder(Request $request)
    {
        $session = $request->getSession();
        $builder = $this->formFactory
            ->createBuilder(SearchCustomerType::class);
        $searchForm = $builder->getForm();

        $viewData = $session->get('eccube.admin.customer.search', []);
        $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

        // ?????????????????????????????????????????????.
        $qb = $this->customerRepository
            ->getQueryBuilderBySearchData($searchData);

        return $qb;
    }

    /**
     * ?????????????????????????????????????????????.
     *
     * @param Request $request
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getProductQueryBuilder(Request $request)
    {
        $session = $request->getSession();
        $builder = $this->formFactory
            ->createBuilder(SearchProductType::class);
        $searchForm = $builder->getForm();

        $viewData = $session->get('eccube.admin.product.search', []);
        $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

        // ?????????????????????????????????????????????.
        $qb = $this->productRepository
            ->getQueryBuilderBySearchDataForAdmin($searchData);

        return $qb;
    }
}
