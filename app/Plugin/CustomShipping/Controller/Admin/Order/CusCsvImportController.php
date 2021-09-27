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

namespace Plugin\CustomShipping\Controller\Admin\Order;

use Eccube\Controller\Admin\AbstractCsvImportController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Form\Type\Admin\CsvImportType;
use Eccube\Repository\ShippingRepository;
use Eccube\Service\CsvImportService;
use Eccube\Service\OrderStateMachine;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Plugin\CustomShipping\Entity\CusShipping;
use plugin\CustomShipping\Repository\CusShippingRepository;
use Symfony\Component\HttpFoundation\Response;
use Eccube\Repository\OrderItemRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\Master\OrderStatusRepository;

class CusCsvImportController extends AbstractCsvImportController
{
    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var ShippingRepository
     */
    private $shippingRepository;

    /**
     * @var OrderItemRepository
     */
    private $orderItemRepository;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var CusShipping
     */
    private $cusShipping;

    /**
     * @var CusShippingRepository
     */
    private $cusShippingRepository;

    /**
     * @var OrderStateMachine
     */
    protected $orderStateMachine;

    public function __construct(
        ShippingRepository $shippingRepository,
        OrderStateMachine $orderStateMachine,
        CusShippingRepository $cusShippingRepository,
        CusShipping $cusShipping,
        OrderItemRepository $orderItemRepository,
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository
    ) {
        $this->shippingRepository = $shippingRepository;
        $this->orderStateMachine = $orderStateMachine;;
        $this->cusShippingRepository = $cusShippingRepository;
        $this->cusShipping = $cusShipping;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
    }

    /**
     * 出荷CSVアップロード
     *
     * @Route("/%eccube_admin_route%/order/shipping_csv_upload", name="admin_shipping_csv_import")
     * @Template("@admin/Order/csv_shipping.twig")
     *
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function csvShipping(Request $request)
    {
        $form = $this->formFactory->createBuilder(CsvImportType::class)->getForm();
        $columnConfig = $this->getColumnConfig();
        $errors = [];
        if ($request->getMethod() === 'POST') {
            
            $form->handleRequest($request);
            if ($form->isValid()) {
                $formFile = $form['import_file']->getData();
                if (!empty($formFile)) {
                    $csv = $this->getImportData($formFile);
                    

                    try {
                        $this->entityManager->getConfiguration()->setSQLLogger(null);
                        $this->entityManager->getConnection()->beginTransaction();

                        $this->loadCsv($csv, $errors);

                        if ($errors) {
                            $this->entityManager->getConnection()->rollBack();
                        } else {
                            $this->entityManager->flush();
                            $this->entityManager->getConnection()->commit();

                            $this->addInfo('admin.common.csv_upload_complete', 'admin');
                        }
                    } finally {
                        $this->removeUploadedFile();
                    }
                }
            }
        }

        return [
            'form' => $form->createView(),
            'headers' => $columnConfig,
            'errors' => $errors,
        ];
    }

    /**
     * 出荷CSVアップロードCustom
     *
     * @Route("/%eccube_admin_route%/cus/order/shipping_csv_upload", name="admin_shipping_csv_upload_custom")
     * @Template("@admin/Order/csv_shipping.twig")
     *
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function csvShipping_cus(Request $request)
    {
        $body = file_get_contents($_FILES['upload_shipping_csv']['tmp_name']);
        $body = mb_convert_encoding($body, 'utf8', 'shift-jis');
        $data = str_getcsv($body, "\n");
        array_walk($data, function (&$a) use ($data) {
            $a = str_getcsv($a);
        });
        if (isset($data[0]) && isset($data[0][15]) && $data[0][15] == "出荷Id" && isset($data[0][14]) && $data[0][14] == "お問い合わせ番号") {
            for ($i = 1; $i < count($data); $i++) {
                if (empty($data[$i][14])) {
                    break;
                }
                $cus_shipping_id = $data[$i][15];
                $cus_tracking_number = $data[$i][14];

                $cusshipping = $this->cusShippingRepository->find($cus_shipping_id);

                $cusshipping->setCusTrackNumber($cus_tracking_number);
                $cusshipping->setCusShippingStatus(1);
                $em = $this->getDoctrine()->getManager();
                $em->persist($cusshipping);
                $em->flush();

                $OrderItems = $this->orderItemRepository->findBy([
                    'Order' => $data[$i][0],
                    'cus_shipping_id' => $data[$i][15]
                ]);
                foreach ($OrderItems as $OrderItem) {
                    $OrderItem->setCusShippingStatusId(1);
                    $OrderItem->setCusOrderStatusId(5);
                    $OrderItem->setIsOrderCsvDownload(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($OrderItem);
                    $em->flush();
                }
            }

            $cnt = $this->orderItemRepository->count([
                'Order' => $cusshipping->getCusOrderId(),
                'OrderItemType' => 1,
                'cus_shipping_status_id' => 0
            ]);

            if ($cnt === 0) {
                $Order = $this->orderRepository->find($cusshipping->getCusOrderId());
                $OrderStatus = $this->orderStatusRepository->find(5);
                $Order->setOrderStatus($OrderStatus);
                $em = $this->getDoctrine()->getManager();
                $em->persist($Order);
                $em->flush();
                $Shipping = $this->shippingRepository->findBy(['Order'=>$cusshipping->getCusOrderId()])[0];
                $Shipping->setShippingDate(new \DateTime());
                $em = $this->getDoctrine()->getManager();
                $em->persist($Shipping);
                $em->flush();
            }

            return $this->redirectToRoute('admin_order_manage_by_products');
        } else {
            $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/EC1027/order/managebyproducts";
            return new Response("<script>alert('CSVのフォーマットが一致しません'); window.location.href='" . $url . "'</script>");
        }
    }

    /**
     * 出荷CSVアップロードCustom_orderpage
     *
     * @Route("/%eccube_admin_route%/cus/order/shipping_csv_upload/order_page", name="admin_shipping_csv_upload_custom_order_page")
     * @Template("@admin/Order/csv_shipping.twig")
     *
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function csvShipping_cus_order_page(Request $request)
    {
        $body = file_get_contents($_FILES['upload_shipping_csv']['tmp_name']);
        $body = mb_convert_encoding($body, 'utf8', 'shift-jis');
        $data = str_getcsv($body, "\n");
        
        array_walk($data, function (&$a) use ($data) {
            $a = str_getcsv($a);
        });
        if (isset($data[0]) && isset($data[0][14]) && $data[0][14] == "お問い合わせ番号") {
            for ($i = 1; $i < count($data); $i++) {
                if (empty($data[$i][14])) {
                    $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/EC1027/order";
                    return new Response("<script>alert('CSVのフォーマットが一致しません'); window.location.href='" . $url . "'</script>");
                }
                $order_id = $data[$i][0];

                $tracking_number = $data[$i][14];

                $Shipping = $this->shippingRepository->findOneBy([
                    'Order' => $order_id
                ]);
                $Shipping->setTrackingNumber($tracking_number);
                $Shipping->setShippingDate(new \DateTime());
                $em = $this->getDoctrine()->getManager();
                $em->persist($Shipping);
                $em->flush();
                
                $Order = $this->orderRepository->find($order_id);
                $OrderStatus = $this->orderStatusRepository->find(5);
                $Order->setOrderStatus($OrderStatus);
                $em = $this->getDoctrine()->getManager();
                $em->persist($Order);
                $em->flush();

                $OrderItems = $this->orderItemRepository->findBy([
                    'Order' => $data[$i][0],
                    'OrderItemType' => 1
                ]);
                
                foreach ($OrderItems as $OrderItem) {
                    $OrderItem->setCusShippingStatusId(1);
                    $OrderItem->setCusOrderStatusId(5);
                    
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($OrderItem);
                    $em->flush();
                }
            }

            return $this->redirectToRoute('admin_order');
        } else {
            $url = $request->getScheme() . '://' . $request->getHttpHost() . $request->getBasePath() . "/EC1027/order";
            return new Response("<script>alert('CSVのフォーマットが一致しません'); window.location.href='" . $url . "'</script>");
        }
    }

    protected function loadCsv(CsvImportService $csv, &$errors)
    {
        $columnConfig = $this->getColumnConfig();

        if ($csv === false) {
            $errors[] = trans('admin.common.csv_invalid_format');
        }

        // 必須カラムの確認
        $requiredColumns = array_map(function ($value) {
            return $value['name'];
        }, array_filter($columnConfig, function ($value) {
            return $value['required'];
        }));
        $csvColumns = $csv->getColumnHeaders();
        if (count(array_diff($requiredColumns, $csvColumns)) > 0) {
            $errors[] = trans('admin.common.csv_invalid_format');

            return;
        }

        // 行数の確認
        $size = count($csv);
        if ($size < 1) {
            $errors[] = trans('admin.common.csv_invalid_format');

            return;
        }

        $columnNames = array_combine(array_keys($columnConfig), array_column($columnConfig, 'name'));

        foreach ($csv as $line => $row) {
            // 出荷IDがなければエラー
            if (!isset($row[$columnNames['id']])) {
                $errors[] = trans('admin.common.csv_invalid_required', ['%line%' => $line + 1, '%name%' => $columnNames['id']]);
                continue;
            }

            /* @var Shipping $Shipping */
            $Shipping = is_numeric($row[$columnNames['id']]) ? $this->shippingRepository->find($row[$columnNames['id']]) : null;

            // 存在しない出荷IDはエラー
            if (is_null($Shipping)) {
                $errors[] = trans('admin.common.csv_invalid_not_found', ['%line%' => $line + 1, '%name%' => $columnNames['id']]);
                continue;
            }

            if (isset($row[$columnNames['tracking_number']])) {
                $Shipping->setTrackingNumber($row[$columnNames['tracking_number']]);
            }

            if (isset($row[$columnNames['shipping_date']])) {
                // 日付フォーマットが異なる場合はエラー
                $shippingDate = \DateTime::createFromFormat('Y-m-d', $row[$columnNames['shipping_date']]);
                if ($shippingDate === false) {
                    $errors[] = trans('admin.common.csv_invalid_date_format', ['%line%' => $line + 1, '%name%' => $columnNames['shipping_date']]);
                    continue;
                }

                $shippingDate->setTime(0, 0, 0);
                $Shipping->setShippingDate($shippingDate);
            }

            $Order = $Shipping->getOrder();
            $RelateShippings = $Order->getShippings();
            $allShipped = true;
            foreach ($RelateShippings as $RelateShipping) {
                if (!$RelateShipping->getShippingDate()) {
                    $allShipped = false;
                    break;
                }
            }
            $OrderStatus = $this->entityManager->find(OrderStatus::class, OrderStatus::DELIVERED);
            if ($allShipped) {
                if ($this->orderStateMachine->can($Order, $OrderStatus)) {
                    $this->orderStateMachine->apply($Order, $OrderStatus);
                } else {
                    $from = $Order->getOrderStatus()->getName();
                    $to = $OrderStatus->getName();
                    $errors[] = trans('admin.order.failed_to_change_status', [
                        '%name%' => $Shipping->getId(),
                        '%from%' => $from,
                        '%to%' => $to,
                    ]);
                }
            }
        }
    }

    /**
     * アップロード用CSV雛形ファイルダウンロード
     *
     * @Route("/%eccube_admin_route%/order/csv_template", name="admin_shipping_csv_template")
     */
    public function csvTemplate(Request $request)
    {
        $columns = array_column($this->getColumnConfig(), 'name');
        // var_export($columns);die;

        return $this->sendTemplateResponse($request, $columns, 'shipping.csv');
    }

    protected function getColumnConfig()
    {
        return [
            'id' => [
                'name' => trans('admin.order.shipping_csv.shipping_id_col'),
                'description' => trans('admin.order.shipping_csv.shipping_id_description'),
                'required' => true,
            ],
            'tracking_number' => [
                'name' => trans('admin.order.shipping_csv.tracking_number_col'),
                'description' => trans('admin.order.shipping_csv.tracking_number_description'),
                'required' => false,
            ],
            'shipping_date' => [
                'name' => trans('admin.order.shipping_csv.shipping_date_col'),
                'description' => trans('admin.order.shipping_csv.shipping_date_description'),
                'required' => true,
            ],
        ];
    }
}
