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

use DateTime;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\ExportCsvRow;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\OrderItem;
use Eccube\Entity\OrderPdf;
use Eccube\Entity\Shipping;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Admin\OrderPdfType;
use Eccube\Form\Type\Admin\SearchOrderType;
use Plugin\CustomShipping\Form\Type\Admin\CusSearchOrderItemType;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Repository\Master\ProductStatusRepository;
use Eccube\Repository\Master\SexRepository;
use Eccube\Repository\OrderPdfRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\ProductStockRepository;
use Eccube\Service\CsvExportService;
use Eccube\Service\MailService;
use Eccube\Service\OrderPdfService;
use Eccube\Service\OrderStateMachine;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Eccube\Repository\OrderItemRepository;
use Plugin\CustomShipping\Repository\CusShippingRepository;
use Plugin\CustomShipping\Entity\CusShipping;
use Eccube\Repository\DeliveryRepository;
use Eccube\Repository\DeliveryFeeRepository;
use Eccube\Repository\ShippingRepository;

class CustomOrderController extends AbstractController
{
    /**
     * @var ShippingRepository;
     */
    protected $shippingRepository;

    /**
     * @var DeliveryRepository;
     */
    protected $deliveryRepository;

    /**
     * @var DeliveryFeeRepository;
     */
    protected $deliveryFeeRepository;

    /**
     * @var CusShipping;
     */
    protected $cusShipping;


    /**
     * @var CusShippingRepository;
     */
    protected $cusShippingRepository;


    /**
     * @var CusSearchOrderItemType
     */
    protected $cusSearchOrderItemType;

    /**
     * @var OrderItemRepository
     */
    protected $orderItemRepository;

    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;

    /**
     * @var CsvExportService
     */
    protected $csvExportService;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var SexRepository
     */
    protected $sexRepository;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var ProductStatusRepository
     */
    protected $productStatusRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /** @var OrderPdfRepository */
    protected $orderPdfRepository;

    /**
     * @var ProductStockRepository
     */
    protected $productStockRepository;

    /** @var OrderPdfService */
    protected $orderPdfService;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var OrderStateMachine
     */
    protected $orderStateMachine;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * OrderController constructor.
     *
     * @param PurchaseFlow $orderPurchaseFlow
     * @param CsvExportService $csvExportService
     * @param CustomerRepository $customerRepository
     * @param PaymentRepository $paymentRepository
     * @param SexRepository $sexRepository
     * @param OrderStatusRepository $orderStatusRepository
     * @param PageMaxRepository $pageMaxRepository
     * @param ProductStatusRepository $productStatusRepository
     * @param ProductStockRepository $productStockRepository
     * @param OrderRepository $orderRepository
     * @param OrderPdfRepository $orderPdfRepository
     * @param ValidatorInterface $validator
     * @param OrderStateMachine $orderStateMachine ;
     * @param OrderItemRepository $orderItemRepository;
     * @param CusShipping $cusShipping;
     * @param CusShippingRepository $cusShippingRepository;
     * @param CusSearchOrderItemType $cusSearchOrderItemType;
     * @param DeliveryRepository $deliveryRepository;
     * @param DeliveryFeeRepository $deliveryFeeRepository;
     * @param ShippingRepository $shippingRepository;
     */
    public function __construct(
        PurchaseFlow $orderPurchaseFlow,
        CsvExportService $csvExportService,
        CustomerRepository $customerRepository,
        PaymentRepository $paymentRepository,
        SexRepository $sexRepository,
        OrderStatusRepository $orderStatusRepository,
        PageMaxRepository $pageMaxRepository,
        ProductStatusRepository $productStatusRepository,
        ProductStockRepository $productStockRepository,
        OrderRepository $orderRepository,
        OrderPdfRepository $orderPdfRepository,
        ValidatorInterface $validator,
        OrderStateMachine $orderStateMachine,
        MailService $mailService,
        OrderItemRepository $orderItemRepository,
        CusSearchOrderItemType $cusSearchOrderItemType,
        CusShippingRepository $cusShippingRepository,
        CusShipping $cusShipping,
        DeliveryRepository $deliveryRepository,
        DeliveryFeeRepository $deliveryFeeRepository,
        ShippingRepository $shippingRepository
    ) {
        $this->purchaseFlow = $orderPurchaseFlow;
        $this->csvExportService = $csvExportService;
        $this->customerRepository = $customerRepository;
        $this->paymentRepository = $paymentRepository;
        $this->sexRepository = $sexRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->pageMaxRepository = $pageMaxRepository;
        $this->productStatusRepository = $productStatusRepository;
        $this->productStockRepository = $productStockRepository;
        $this->orderRepository = $orderRepository;
        $this->orderPdfRepository = $orderPdfRepository;
        $this->validator = $validator;
        $this->orderStateMachine = $orderStateMachine;
        $this->mailService = $mailService;
        $this->orderItemRepository = $orderItemRepository;
        $this->cusSearchOrderItemType = $cusSearchOrderItemType;
        $this->cusShipping = $cusShipping;
        $this->cusShippingRepository = $cusShippingRepository;
        $this->deliveryFeeRepository = $deliveryFeeRepository;
        $this->deliveryRepository = $deliveryRepository;
        $this->shippingRepository = $shippingRepository;
    }

    /**
     * 受注一覧画面.
     *
     * - 検索条件, ページ番号, 表示件数はセッションに保持されます.
     * - クエリパラメータでresume=1が指定された場合、検索条件, ページ番号, 表示件数をセッションから復旧します.
     * - 各データの, セッションに保持するアクションは以下の通りです.
     *   - 検索ボタン押下時
     *      - 検索条件をセッションに保存します
     *      - ページ番号は1で初期化し、セッションに保存します。
     *   - 表示件数変更時
     *      - クエリパラメータpage_countをセッションに保存します。
     *      - ただし, mtb_page_maxと一致しない場合, eccube_default_page_countが保存されます.
     *   - ページング時
     *      - URLパラメータpage_noをセッションに保存します.
     *   - 初期表示
     *      - 検索条件は空配列, ページ番号は1で初期化し, セッションに保存します.
     *
     * @Route("/%eccube_admin_route%/order", name="admin_order")
     * @Route("/%eccube_admin_route%/order/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_order_page")
     * @Template("@admin/Order/index.twig")
     */
    public function index(Request $request, $page_no = null, PaginatorInterface $paginator)
    {

        $builder = $this->formFactory
            ->createBuilder(SearchOrderType::class);

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ORDER_INDEX_INITIALIZE, $event);

        $searchForm = $builder->getForm();


        /**
         * ページの表示件数は, 以下の順に優先される.
         * - リクエストパラメータ
         * - セッション
         * - デフォルト値
         * また, セッションに保存する際は mtb_page_maxと照合し, 一致した場合のみ保存する.
         **/
        $page_count = $this->session->get(
            'eccube.admin.order.search.page_count',
            $this->eccubeConfig->get('eccube_default_page_count')
        );

        $page_count_param = (int) $request->get('page_count');
        $pageMaxis = $this->pageMaxRepository->findAll();


        if ($page_count_param) {
            // var_export($page_count_param);die;
            foreach ($pageMaxis as $pageMax) {
                if ($page_count_param == $pageMax->getName()) {
                    $page_count = $pageMax->getName();
                    $this->session->set('eccube.admin.order.search.page_count', $page_count);
                    break;
                }
            }
        }
        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);

            if ($searchForm->isValid()) {
                /**
                 * 検索が実行された場合は, セッションに検索条件を保存する.
                 * ページ番号は最初のページ番号に初期化する.
                 */
                $page_no = 1;
                $searchData = $searchForm->getData();

                // 検索条件, ページ番号をセッションに保持.
                $this->session->set('eccube.admin.order.search', FormUtil::getViewData($searchForm));
                $this->session->set('eccube.admin.order.search.page_no', $page_no);
            } else {
                // 検索エラーの際は, 詳細検索枠を開いてエラー表示する.
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $page_count,
                    'has_errors' => true,
                ];
            }
        } else {

            if (null !== $page_no || $request->get('resume')) {
                /*
                 * ページ送りの場合または、他画面から戻ってきた場合は, セッションから検索条件を復旧する.
                 */
                if ($page_no) {
                    // ページ送りで遷移した場合.
                    $this->session->set('eccube.admin.order.search.page_no', (int) $page_no);
                } else {
                    // 他画面から遷移した場合.
                    $page_no = $this->session->get('eccube.admin.order.search.page_no', 1);
                }
                $viewData = $this->session->get('eccube.admin.order.search', []);
                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
            } else {
                /**
                 * 初期表示の場合.
                 */
                $page_no = 1;
                $viewData = [];

                if ($statusId = (int) $request->get('order_status_id')) {
                    $viewData = ['status' => $statusId];
                }

                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

                // セッション中の検索条件, ページ番号を初期化.
                $this->session->set('eccube.admin.order.search', $viewData);
                $this->session->set('eccube.admin.order.search.page_no', $page_no);
            }
        }

        $qb = $this->orderRepository->getQueryBuilderBySearchDataForAdmin($searchData);


        $event = new EventArgs(
            [
                'qb' => $qb,
                'searchData' => $searchData,
            ],
            $request
        );

        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ORDER_INDEX_SEARCH, $event);

        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $page_count
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $page_count,
            'has_errors' => false,
            'OrderStatuses' => $this->orderStatusRepository->findBy(['id' => [1,3,5,9]], ['sort_no' => 'ASC']),
        ];
    }


    /**
     * @Route("/%eccube_admin_route%/order/managebyproducts", name="admin_order_manage_by_products")
     * @Route("/%eccube_admin_route%/order/managebyproducts/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_order_manage_by_products_page")
     * @Template("@CustomShipping/admin/order/managebyproducts.twig")
     */
    public function manageOrderByProducts(Request $request, $page_no = null, PaginatorInterface $paginator)
    {

        $builder = $this->formFactory
            ->createBuilder(CusSearchOrderItemType::class);

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ORDER_INDEX_INITIALIZE, $event);

        $searchForm = $builder->getForm();

        /**
         * ページの表示件数は, 以下の順に優先される.
         * - リクエストパラメータ
         * - セッション
         * - デフォルト値
         * また, セッションに保存する際は mtb_page_maxと照合し, 一致した場合のみ保存する.
         **/
        $page_count = $this->session->get(
            'eccube.admin.order.search.page_count.cus',
            $this->eccubeConfig->get('eccube_default_page_count')
        );

        $page_count_param = (int) $request->get('page_count');
        $pageMaxis = $this->pageMaxRepository->findAll();

        if ($page_count_param) {
            foreach ($pageMaxis as $pageMax) {
                if ($page_count_param == $pageMax->getName()) {
                    $page_count = $pageMax->getName();
                    $this->session->set('eccube.admin.order.search.page_count.cus', $page_count);
                    break;
                }
            }
        }

        if ('POST' === $request->getMethod()) {

            $searchForm->handleRequest($request);

            if ($searchForm->isValid()) {
                /**
                 * 検索が実行された場合は, セッションに検索条件を保存する.
                 * ページ番号は最初のページ番号に初期化する.
                 */
                $page_no = 1;
                $searchData = $searchForm->getData();

                // 検索条件, ページ番号をセッションに保持.
                $this->session->set('eccube.admin.order.search', FormUtil::getViewData($searchForm));
                $this->session->set('eccube.admin.order.search.page_no', $page_no);
            } else {
                // 検索エラーの際は, 詳細検索枠を開いてエラー表示する.
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $page_count,
                    'has_errors' => true,
                ];
            }
        } else {

            if (null !== $page_no || $request->get('resume')) {
                /*
                * ページ送りの場合または、他画面から戻ってきた場合は, セッションから検索条件を復旧する.
                */
                if ($page_no) {
                    // ページ送りで遷移した場合.
                    $this->session->set('eccube.admin.order.search.page_no', (int) $page_no);
                } else {
                    // 他画面から遷移した場合.
                    $page_no = $this->session->get('eccube.admin.order.search.page_no', 1);
                }
                $viewData = $this->session->get('eccube.admin.order.search', []);
                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
            } else {
                /**
                 * 初期表示の場合.
                 */
                $page_no = 1;
                $viewData = [];

                if ($statusId = (int) $request->get('order_status_id')) {
                    $viewData = ['status' => $statusId];
                }

                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

                // セッション中の検索条件, ページ番号を初期化.
                $this->session->set('eccube.admin.order.search', $viewData);
                $this->session->set('eccube.admin.order.search.page_no', $page_no);
            }
        }
        $qb = $this->orderItemRepository->getQueryBuilderBySearchDataForAdmin($searchData)->orderBy('o.id', 'DESC')->addOrderBy('oi.cus_shipping_id');
        $event = new EventArgs(
            [
                'qb' => $qb,
                'searchData' => $searchData,
            ],
            $request
        );

        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ORDER_INDEX_SEARCH, $event);

        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $page_count
        );

        $items = [];
        $rowspans = [];
        $lastCusShippingId = 0;
        $rowspan = 1;

        foreach ($pagination as $OrderItem) {
            $CusShippingId = $OrderItem->getCusShippingId();
            if (!empty($CusShippingId) && $lastCusShippingId == $CusShippingId) {
                $OrderItem->is_rowspan = false;
                $rowspan++;
            } else {
                $OrderItem->is_rowspan = true;
                $lastCusShippingId = $CusShippingId;
                $rowspan = 1;
            }
            $rowspans[$CusShippingId] = $rowspan;
            $items[] = $OrderItem;
        }

        // usort($items, function ($a, $b) {
        //     return $b->getOrder()->getCreateDate() <=> $a->getOrder()->getCreateDate();
        // });
        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'OrderItems' => $items,
            'rowspans' => $rowspans,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $page_count,
            'has_errors' => false,
            'OrderStatuses' => $this->orderStatusRepository->findBy(['id' => [1,3,5,9]], ['sort_no' => 'ASC']),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/order/bulk_delete", name="admin_order_bulk_delete", methods={"POST"})
     */
    public function bulkDelete(Request $request)
    {
        $this->isTokenValid();
        $ids = $request->get('ids');
        foreach ($ids as $order_id) {
            $Order = $this->orderRepository
                ->find($order_id);
            if ($Order) {
                $this->entityManager->remove($Order);
                log_info('受注削除', [$Order->getId()]);
            }
        }

        $this->entityManager->flush();

        $this->addSuccess('admin.common.delete_complete', 'admin');

        return $this->redirect($this->generateUrl('admin_order', ['resume' => Constant::ENABLED]));
    }

    /**
     * make shipping group
     *
     * @Route("/%eccube_admin_route%/cus/order/make-shipping-group", name="admin_order_make_shipping_group")
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function makeShippingGroup(Request $request)
    {


        $OrderItems = [];
        $ids = $request->get('item-check');
        foreach ($ids as $orderItemId) {
            $OrderItems[] = $this->orderItemRepository->find($orderItemId);
        }
        $cus_shipping_charge = 0;
        $withShippingCharge = 0;
        $cus_sub_total = 0;

        for ($i = 0; $i < count($OrderItems); $i++) {
            if($OrderItems[$i]->getProduct()->no_fee != 1){

                if($OrderItems[$i]->getProduct()->shipping_charge == Null){
                    $sale_type_id = $OrderItems[$i]->getProductClass()->getSaleType()->getId();
                    $pref_id = $OrderItems[$i]->getOrder()->getPref()->getId();
                    // $delivery_id= $this->deliveryRepository->findOneBy(['SaleType'=>$sale_type_id]);
                    
                    $delivery_id = 1;
                    if($OrderItems[$i]->getOrder()->getDeliveryMethodFlag() != null){
                        $delivery_id = $OrderItems[$i]->getOrder()->getDeliveryMethodFlag();
                    }

                    $delivery_fee = $this->deliveryFeeRepository->findOneBy([
                        'Delivery'=>$delivery_id,
                        'Pref'=>$pref_id
                    ])->getFee();
                    $withShippingCharge = $delivery_fee;
                }
                else{
    
                    $cus_shipping_charge += $OrderItems[$i]->getProduct()->shipping_charge * $OrderItems[$i]->getQuantity();
                }
            }

            $cus_sub_total += $OrderItems[$i]->getTotalPrice();
        }
        $cus_shipping_charge += $withShippingCharge;
        $cus_total = $cus_sub_total + $cus_shipping_charge + $OrderItems[0]->getOrder()->getCharge();
        $cus_total_by_tax = $cus_sub_total + $cus_shipping_charge + $OrderItems[0]->getOrder()->getCharge();
        $cus_payment_total = $cus_sub_total + $cus_shipping_charge + $OrderItems[0]->getOrder()->getCharge();

        $cusshipping = new CusShipping();
        $cusshipping->setCusTrackNumber("");
        $cusshipping->setCusOrderId($OrderItems[0]->getOrder()->getId());
        $cusshipping->setCusShippingCharge($cus_shipping_charge);
        $cusshipping->setCusSubTotal($cus_sub_total);
        $cusshipping->setCusTotal($cus_total);
        $cusshipping->setCusTotalByTax($cus_total_by_tax);
        $cusshipping->setCusPaymentTotal($cus_payment_total);
        $cusshipping->setCusShippingStatus(0);


        $em = $this->getDoctrine()->getManager();
        $em->persist($cusshipping);
        $em->flush();
        $cusshipping_id = $cusshipping->getId();

        for ($i = 0; $i < count($ids); $i++) {
            $OrderItem = $this->orderItemRepository->find($ids[$i]);
            $OrderItem->setCusShippingId($cusshipping_id);
            $em = $this->getDoctrine()->getManager();
            $em->persist($OrderItem);
            $em->flush();
        }

        // $filename = 'order_' . (new \DateTime())->format('YmdHis') . '.csv';
        // $response = $this->exportCsv_cus($request, CsvType::CSV_TYPE_ORDER, $filename, $cusshipping_id);
        // log_info('受注CSV出力ファイル名', [$filename]);

        return $this->redirectToRoute('admin_order_manage_by_products');
    }

    /**
     * 受注CSVの出力 multiple.
     *
     * @Route("/%eccube_admin_route%/cus/order/export/order", name="admin_order_export_order_custom")
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function exportOrder(Request $request)
    {
        $ids = $request->get('item-check');
        $shi_ids = $request->get('shipping-check') ? $request->get('shipping-check') : [];

        if ($ids == NULL && $shi_ids == NULL) {
            return new Response("<h1>Error<h1>");
        }
        if (count($shi_ids) > 0) {
            foreach ($shi_ids as $shi_id) {
                $Orderitems = $this->orderItemRepository->findBy(['cus_shipping_id' => $shi_id]);
                foreach ($Orderitems as $item) {
                    $item->setIsOrderCsvDownload(1);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($item);
                    $em->flush();
                }
            }
            $cusShipping = $this->cusShippingRepository->find($shi_id);
            if ($cusShipping->getCusShippingDate() == Null) {

                $cusShipping->setCusShippingDate(new \DateTime());
                $em = $this->getDoctrine()->getManager();
                $em->persist($cusShipping);
                $em->flush();
            }
        }
        if ($ids) {
            foreach ($ids as $orderItemId) {
                $OrderItem = $this->orderItemRepository->find($orderItemId);
                $cus_shipping_charge = 0;
                if($OrderItem->getProduct()->no_fee != 1){

                    if($OrderItem->getProduct()->shipping_charge == Null){
                        $sale_type_id = $OrderItem->getProductClass()->getSaleType()->getId();
                        $pref_id = $OrderItem->getOrder()->getPref()->getId();
                        // $delivery_id= $this->deliveryRepository->findOneBy(['SaleType'=>$sale_type_id]);
                        $delivery_id = 1;
                        if($OrderItem->getOrder()->getDeliveryMethodFlag() != null){
                            $delivery_id = $OrderItem->getOrder()->getDeliveryMethodFlag();
                        }
                        $delivery_fee = $this->deliveryFeeRepository->findOneBy([
                            'Delivery'=>$delivery_id,
                            'Pref'=>$pref_id
                        ])->getFee();
                        $cus_shipping_charge += $delivery_fee;
                    }
                    else{
        
                        $cus_shipping_charge += $OrderItem->getProduct()->shipping_charge * $OrderItem->getQuantity();
                    }
                }
                $cus_sub_total = $OrderItem->getTotalPrice();
                $cus_total = $cus_sub_total + $cus_shipping_charge + $OrderItem->getOrder()->getCharge();
                $cus_total_by_tax = $cus_sub_total + $cus_shipping_charge + $OrderItem->getOrder()->getCharge();
                $cus_payment_total = $cus_sub_total + $cus_shipping_charge + $OrderItem->getOrder()->getCharge();
                $cusshipping = new CusShipping();
                $cusshipping->setCusTrackNumber("");
                $cusshipping->setCusShippingDate(new \DateTime());
                $cusshipping->setCusOrderId($OrderItem->getOrder()->getId());
                $cusshipping->setCusShippingCharge($cus_shipping_charge);
                $cusshipping->setCusSubTotal($cus_sub_total);
                $cusshipping->setCusTotal($cus_total);
                $cusshipping->setCusTotalByTax($cus_total_by_tax);
                $cusshipping->setCusPaymentTotal($cus_payment_total);
                $cusshipping->setCusShippingStatus(0);

                $em = $this->getDoctrine()->getManager();
                $em->persist($cusshipping);
                $em->flush();
                $cusshipping_id = $cusshipping->getId();


                $OrderItem->setCusShippingId($cusshipping_id);
                $OrderItem->setIsOrderCsvDownload(1);
                $em = $this->getDoctrine()->getManager();
                $em->persist($OrderItem);
                $em->flush();

                array_push($shi_ids, $cusshipping_id);
            }
        }


        $filename = 'order_' . (new \DateTime())->format('YmdHis') . '.csv';
        $response = $this->exportCsv_cus($request, CsvType::CSV_TYPE_ORDER, $filename, $shi_ids);
        log_info('受注CSV出力ファイル名', [$filename]);

        return $response;
    }

    /**
     * 受注CSVの出力 multiple. order_page
     *
     * @Route("/%eccube_admin_route%/cus/order/export/order/page", name="admin_order_export_order_custom_order_page")
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function exportOrder_orderPage(Request $request)
    {
        $shi_ids = $request->get('ids');
       

        if ($shi_ids == NULL) {
            return new Response("<h1>Error<h1>");
        }     
        $order_ids = [];
        foreach($shi_ids as $shi_id){
            $order_ids[]=$this->shippingRepository->find($shi_id)->getOrder()->getId();
        }


        $filename = 'order_' . (new \DateTime())->format('YmdHis') . '.csv';
        $response = $this->exportCsv_cus_order_page($request, CsvType::CSV_TYPE_ORDER, $filename, $order_ids);
        log_info('受注CSV出力ファイル名', [$filename]);

        return $response;
    }
    /**
     * 受注CSVの出力 single.
     *
     * @Route("/%eccube_admin_route%/cus/order/export/order/{id}", name="admin_order_export_order_custom_by_one")
     *
     * @param Request $request
     * @param OrderItem $OrderItem
     *
     * @return StreamedResponse
     */
    public function exportOrder_one(Request $request, OrderItem $OrderItem)
    {
        $orderItemId = $OrderItem->getId();
        $cus_shipping_charge = 0;
        if($OrderItem->getProduct()->no_fee != 1){

            if($OrderItem->getProduct()->shipping_charge == Null){
                $sale_type_id = $OrderItem->getProductClass()->getSaleType()->getId();
                $pref_id = $OrderItem->getOrder()->getPref()->getId();
                // $delivery_id= $this->deliveryRepository->findOneBy(['SaleType'=>$sale_type_id]);
                $delivery_id = 1;
                if($OrderItem->getOrder()->getDeliveryMethodFlag() != null){
                    $delivery_id = $OrderItem->getOrder()->getDeliveryMethodFlag();
                }
                $delivery_fee = $this->deliveryFeeRepository->findOneBy([
                    'Delivery'=>$delivery_id,
                    'Pref'=>$pref_id
                ])->getFee();
                $cus_shipping_charge += $delivery_fee;
            }
            else{
    
                $cus_shipping_charge += $OrderItem->getProduct()->shipping_charge * $OrderItem->getQuantity();
            }
        }
        $cus_sub_total = $OrderItem->getTotalPrice();

        $cus_total = $cus_sub_total + $cus_shipping_charge + $OrderItem->getOrder()->getCharge();
        $cus_total_by_tax = $cus_sub_total + $cus_shipping_charge + $OrderItem->getOrder()->getCharge();
        $cus_payment_total = $cus_sub_total + $cus_shipping_charge + $OrderItem->getOrder()->getCharge();

        $cusshipping = new CusShipping();
        $cusshipping->setCusTrackNumber("");
        $cusshipping->setCusShippingDate(new \DateTime());
        $cusshipping->setCusOrderId($OrderItem->getOrder()->getId());
        $cusshipping->setCusShippingCharge($cus_shipping_charge);
        $cusshipping->setCusSubTotal($cus_sub_total);
        $cusshipping->setCusTotal($cus_total);
        $cusshipping->setCusTotalByTax($cus_total_by_tax);
        $cusshipping->setCusPaymentTotal($cus_payment_total);
        $cusshipping->setCusShippingStatus(0);

        $em = $this->getDoctrine()->getManager();
        $em->persist($cusshipping);
        $em->flush();
        $cusshipping_id = $cusshipping->getId();


        $OrderItem->setCusShippingId($cusshipping_id);
        $OrderItem->setIsOrderCsvDownload(1);
        $em = $this->getDoctrine()->getManager();
        $em->persist($OrderItem);
        $em->flush();
        $filename = 'order_' . (new \DateTime())->format('YmdHis') . '.csv';
        $response = $this->exportCsv_cus_one($request, CsvType::CSV_TYPE_ORDER, $filename, $orderItemId);
        log_info('受注CSV出力ファイル名', [$filename]);

        return $response;
    }
    /**
     * 受注CSVの出力 single.
     *
     * @Route("/%eccube_admin_route%/cus/order/export/order/with/shipping_id/{id}", name="admin_order_export_order_custom_by_one_with_shi_id")
     *
     * @param Request $request
     * @param CusShipping $cusShipping
     *
     * @return StreamedResponse
     */
    public function exportOrder_one_width_shi_id(Request $request, CusShipping $cusShippingId)
    {
        $cusShipping = $this->cusShippingRepository->find($cusShippingId);
            if ($cusShipping->getCusShippingDate() == Null) {

                $cusShipping->setCusShippingDate(new \DateTime());
                $em = $this->getDoctrine()->getManager();
                $em->persist($cusShipping);
                $em->flush();
            }
        $OrderItems = $this->orderItemRepository->findBy(['cus_shipping_id' => $cusShippingId]);
        foreach ($OrderItems as $OrderItem) {
            $OrderItem->setIsOrderCsvDownload(1);
            $em = $this->getDoctrine()->getManager();
            $em->persist($OrderItem);
            $em->flush();
        }
        $filename = 'order_' . (new \DateTime())->format('YmdHis') . '.csv';
        $response = $this->exportCsv_cus_one_with_shi($request, CsvType::CSV_TYPE_ORDER, $filename, $cusShippingId->getId());
        log_info('受注CSV出力ファイル名', [$filename]);

        return $response;
    }

    /**
     * 配送CSVの出力.
     *
     * @Route("/%eccube_admin_route%/order/export/shipping", name="admin_order_export_shipping")
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function exportShipping(Request $request)
    {
        $filename = 'shipping_' . (new \DateTime())->format('YmdHis') . '.csv';
        $response = $this->exportCsv($request, CsvType::CSV_TYPE_SHIPPING, $filename);
        log_info('配送CSV出力ファイル名', [$filename]);

        return $response;
    }

    /**
     * @param Request $request
     * @param $csvTypeId
     * @param string $fileName
     *
     * @return StreamedResponse
     */
    protected function exportCsv(Request $request, $csvTypeId, $fileName)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $this->entityManager;
        $em->getConfiguration()->setSQLLogger(null);
        $response = new StreamedResponse();

        $response->setCallback(function () use ($request, $csvTypeId) {
            // CSV種別を元に初期化.
            $this->csvExportService->initCsvType($csvTypeId);

            // ヘッダ行の出力.
            $this->csvExportService->exportHeader_cus();

            // 受注データ検索用のクエリビルダを取得.
            $qb = $this->csvExportService
                ->getOrderQueryBuilder($request);

            // データ行の出力.
            $this->csvExportService->setExportQueryBuilder($qb);
            $this->csvExportService->exportData(function ($entity, $csvService) use ($request) {
                $Csvs = $csvService->getCsvs();

                $Order = $entity;
                $OrderItems = $Order->getOrderItems();

                foreach ($OrderItems as $OrderItem) {
                    $ExportCsvRow = new ExportCsvRow();

                    // CSV出力項目と合致するデータを取得.
                    foreach ($Csvs as $Csv) {
                        // 受注データを検索.
                        $ExportCsvRow->setData($csvService->getData($Csv, $Order));
                        if ($ExportCsvRow->isDataNull()) {
                            // 受注データにない場合は, 受注明細を検索.
                            $ExportCsvRow->setData($csvService->getData($Csv, $OrderItem));
                        }
                        if ($ExportCsvRow->isDataNull() && $Shipping = $OrderItem->getShipping()) {
                            // 受注明細データにない場合は, 出荷を検索.
                            $ExportCsvRow->setData($csvService->getData($Csv, $Shipping));
                        }

                        $event = new EventArgs(
                            [
                                'csvService' => $csvService,
                                'Csv' => $Csv,
                                'OrderItem' => $OrderItem,
                                'ExportCsvRow' => $ExportCsvRow,
                            ],
                            $request
                        );
                        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ORDER_CSV_EXPORT_ORDER, $event);

                        $ExportCsvRow->pushData();
                    }

                    //$row[] = number_format(memory_get_usage(true));
                    // 出力.
                    $csvService->fputcsv($ExportCsvRow->getRow());
                }
            });
        });

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $fileName);
        $response->send();
        return $response;
    }

    /**
     * @param Request $request
     * @param $csvTypeId
     * @param string $fileName
     * @param array $cusshipping_id
     *
     * @return StreamedResponse
     */
    protected function exportCsv_cus(Request $request, $csvTypeId, $fileName, $shi_ids)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $this->entityManager;
        $em->getConfiguration()->setSQLLogger(null);
        $response = new StreamedResponse();

        $response->setCallback(function () use ($request, $csvTypeId, $shi_ids) {
            // CSV種別を元に初期化.
            $this->csvExportService->initCsvType($csvTypeId);

            // ヘッダ行の出力.
            $this->csvExportService->exportHeader_cus();

            // 受注データ検索用のクエリビルダを取得.
            $qb = $this->csvExportService
                ->getOrderQueryBuilder($request);

            // データ行の出力.
            $this->csvExportService->setExportQueryBuilder($qb);
            $this->csvExportService->exportData_cus($request, $shi_ids);
        });

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $fileName);
        $response->send();
        return $response;
    }

    /**
     * @param Request $request
     * @param $csvTypeId
     * @param string $fileName
     * @param array $cusshipping_id
     *
     * @return StreamedResponse
     */
    protected function exportCsv_cus_order_page(Request $request, $csvTypeId, $fileName, $order_ids)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $this->entityManager;
        $em->getConfiguration()->setSQLLogger(null);
        $response = new StreamedResponse();

        $response->setCallback(function () use ($request, $csvTypeId, $order_ids) {
            // CSV種別を元に初期化.
            $this->csvExportService->initCsvType($csvTypeId);

            // ヘッダ行の出力.
            $this->csvExportService->exportHeader_cus_order_page();

            // 受注データ検索用のクエリビルダを取得.
            $qb = $this->csvExportService
                ->getOrderQueryBuilder($request);

            // データ行の出力.
            $this->csvExportService->setExportQueryBuilder($qb);
            $this->csvExportService->exportData_cus_order_page($request, $order_ids);
        });

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $fileName);
        $response->send();
        return $response;
    }

    /**
     * @param Request $request
     * @param $csvTypeId
     * @param string $fileName
    
     * @param int $orderItemId
     *
     * @return StreamedResponse
     */
    protected function exportCsv_cus_one(Request $request, $csvTypeId, $fileName, $orderItemId)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $this->entityManager;
        $em->getConfiguration()->setSQLLogger(null);
        $response = new StreamedResponse();

        $response->setCallback(function () use ($request, $csvTypeId, $orderItemId) {
            // CSV種別を元に初期化.
            $this->csvExportService->initCsvType($csvTypeId);

            // ヘッダ行の出力.
            $this->csvExportService->exportHeader_cus();

            // 受注データ検索用のクエリビルダを取得.
            $qb = $this->csvExportService
                ->getOrderQueryBuilder($request);

            // データ行の出力.
            $this->csvExportService->setExportQueryBuilder($qb);
            $this->csvExportService->exportData_cus_one($request, $orderItemId);
        });

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $fileName);
        $response->send();
        return $response;
    }
    /**
     * @param Request $request
     * @param $csvTypeId
     * @param string $fileName
    
     * @param int $cusShippingId
     *
     * @return StreamedResponse
     */
    protected function exportCsv_cus_one_with_shi(Request $request, $csvTypeId, $fileName, $cusShippingId)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $this->entityManager;
        $em->getConfiguration()->setSQLLogger(null);
        $response = new StreamedResponse();
        $response->setCallback(function () use ($request, $csvTypeId, $cusShippingId) {
            // CSV種別を元に初期化.
            $this->csvExportService->initCsvType($csvTypeId);

            // ヘッダ行の出力.
            $this->csvExportService->exportHeader_cus();

            // 受注データ検索用のクエリビルダを取得.
            $qb = $this->csvExportService
                ->getOrderQueryBuilder($request);

            // データ行の出力.
            $this->csvExportService->setExportQueryBuilder($qb);
            $this->csvExportService->exportData_cus_one_with_shi($request, $cusShippingId);
        });

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename=' . $fileName);
        $response->send();
        return $response;
    }

    /**
     * Update to order status
     *
     * @Route("/%eccube_admin_route%/shipping/{id}/order_status", requirements={"id" = "\d+"}, name="admin_shipping_update_order_status", methods={"PUT"})
     *
     * @param Request $request
     * @param Shipping $Shipping
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateOrderStatus(Request $request, Shipping $Shipping)
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json(['status' => 'NG'], 400);
        }

        $Order = $Shipping->getOrder();
        $OrderStatus = $this->entityManager->find(OrderStatus::class, $request->get('order_status'));

        if (!$OrderStatus) {
            return $this->json(['status' => 'NG'], 400);
        }

        $result = [];
        try {
            if ($Order->getOrderStatus()->getId() == $OrderStatus->getId()) {
                log_info('対応状況一括変更スキップ');
                $result = ['message' => trans('admin.order.skip_change_status', ['%name%' => $Shipping->getId()])];
            } else {
                if ($this->orderStateMachine->can($Order, $OrderStatus)) {
                    if ($OrderStatus->getId() == OrderStatus::DELIVERED) {
                        if (!$Shipping->isShipped()) {
                            $Shipping->setShippingDate(new \DateTime());
                        }
                        $allShipped = true;
                        foreach ($Order->getShippings() as $Ship) {
                            if (!$Ship->isShipped()) {
                                $allShipped = false;
                                break;
                            }
                        }
                        if ($allShipped) {
                            $this->orderStateMachine->apply($Order, $OrderStatus);
                        }
                    } else {
                        $this->orderStateMachine->apply($Order, $OrderStatus);
                    }

                    if ($request->get('notificationMail')) { // for SimpleStatusUpdate
                        $this->mailService->sendShippingNotifyMail($Shipping);
                        $Shipping->setMailSendDate(new \DateTime());
                        $result['mail'] = true;
                    } else {
                        $result['mail'] = false;
                    }
                    // 対応中・キャンセルの更新時は商品在庫を増減させているので商品情報を更新
                    if ($OrderStatus->getId() == OrderStatus::IN_PROGRESS || $OrderStatus->getId() == OrderStatus::CANCEL) {
                        foreach ($Order->getOrderItems() as $OrderItem) {
                            $ProductClass = $OrderItem->getProductClass();
                            if ($OrderItem->isProduct() && !$ProductClass->isStockUnlimited()) {
                                $this->entityManager->flush($ProductClass);
                                $ProductStock = $this->productStockRepository->findOneBy(['ProductClass' => $ProductClass]);
                                $this->entityManager->flush($ProductStock);
                            }
                        }
                    }
                    $this->entityManager->flush($Order);
                    $this->entityManager->flush($Shipping);

                    // 会員の場合、購入回数、購入金額などを更新
                    if ($Customer = $Order->getCustomer()) {
                        $this->orderRepository->updateOrderSummary($Customer);
                        $this->entityManager->flush($Customer);
                    }
                } else {
                    $from = $Order->getOrderStatus()->getName();
                    $to = $OrderStatus->getName();
                    $result = ['message' => trans('admin.order.failed_to_change_status', [
                        '%name%' => $Shipping->getId(),
                        '%from%' => $from,
                        '%to%' => $to,
                    ])];
                }

                $OrderItems = $Order->getOrderItems();
                foreach ($OrderItems as $OrderItem) {
                    $em = $this->getDoctrine()->getManager();
                    $OrderItem->setCusShippingStatusId(1)->setIsOrderCsvDownload(1);
                    $em->persist($OrderItem);
                    $em->flush();
                }
                log_info('対応状況一括変更処理完了', [$Order->getId()]);
            }
        } catch (\Exception $e) {
            log_error('予期しないエラーです', [$e->getMessage()]);

            return $this->json(['status' => 'NG'], 500);
        }

        return $this->json(array_merge(['status' => 'OK'], $result));
    }

    /**
     * Update to order status
     *
     * @Route("/%eccube_admin_route%/shipping/{id}/orderitem_status", requirements={"id" = "\d+"}, name="admin_orderitem_status", methods={"PUT"})
     *
     * @param Request $request
     * @param Shipping $Shipping
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateOrderItemStatus(Request $request, OrderItem $OrderItem)
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json(['status' => 'NG'], 400);
        }

        $Order = $OrderItem->getOrder();
        $OrderStatus = $this->entityManager->find(OrderStatus::class, $request->get('order_status'));

        if (!$OrderStatus) {
            return $this->json(['status' => 'NG'], 400);
        }
        $em = $this->getDoctrine()->getManager();
        $OrderItem->setCusOrderStatusId($request->get('order_status'));
        $em->persist($OrderItem);
        $em->flush();
        $result = ['mail' => trans('OOK', ['%name%' => $OrderItem->getId()])];
        // try {
        //     if ($OrderItem->getCusShippingStatusId() == $OrderStatus->getId()) {
        //         log_info('対応状況一括変更スキップ');
        //         $result = ['message' => trans('admin.order.skip_change_status', ['%name%' => $OrderItem->getId()])];
        //     } else {
        //         $OrderItem->setCusShippingStatusId($request->get('order_status'));
                
                
        //         // if ($this->orderStateMachine->can($Order, $OrderStatus)) {
        //         //     // if ($OrderStatus->getId() == OrderStatus::DELIVERED) {
        //         //     //     if (!$Shipping->isShipped()) {
        //         //     //         $Shipping->setShippingDate(new \DateTime());
        //         //     //     }
        //         //     //     $allShipped = true;
        //         //     //     foreach ($Order->getShippings() as $Ship) {
        //         //     //         if (!$Ship->isShipped()) {
        //         //     //             $allShipped = false;
        //         //     //             break;
        //         //     //         }
        //         //     //     }
        //         //     //     if ($allShipped) {
        //         //     //         $this->orderStateMachine->apply($Order, $OrderStatus);
        //         //     //     }
        //         //     // } else {
        //         //     //     $this->orderStateMachine->apply($Order, $OrderStatus);
        //         //     // }

        //         //     // if ($request->get('notificationMail')) { // for SimpleStatusUpdate
        //         //     //     $this->mailService->sendShippingNotifyMail($Shipping);
        //         //     //     $Shipping->setMailSendDate(new \DateTime());
        //         //     //     $result['mail'] = true;
        //         //     // } else {
        //         //     //     $result['mail'] = false;
        //         //     // }
        //         //     // 対応中・キャンセルの更新時は商品在庫を増減させているので商品情報を更新
        //         //     if ($OrderStatus->getId() == OrderStatus::IN_PROGRESS || $OrderStatus->getId() == OrderStatus::CANCEL) {
        //         //         // foreach ($Order->getOrderItems() as $OrderItem) {
        //         //             // $ProductClass = $OrderItem->getProductClass();
        //         //             // if ($OrderItem->isProduct() && !$ProductClass->isStockUnlimited()) {
        //         //             //     $this->entityManager->flush($ProductClass);
        //         //             //     $ProductStock = $this->productStockRepository->findOneBy(['ProductClass' => $ProductClass]);
        //         //             //     $this->entityManager->flush($ProductStock);
        //         //             // }
        //         //         // }
        //         //     }
        //         //     // $this->entityManager->flush($Order);
        //         //     $this->entityManager->flush($OrderItem);

        //         //     // 会員の場合、購入回数、購入金額などを更新
        //         //     // if ($Customer = $Order->getCustomer()) {
        //         //     //     $this->orderRepository->updateOrderSummary($Customer);
        //         //     //     $this->entityManager->flush($Customer);
        //         //     // }
        //         // } else {
        //         //     $from = $Order->getOrderStatus()->getName();
        //         //     $to = $OrderStatus->getName();
        //         //     $result = ['message' => trans('admin.order.failed_to_change_status', [
        //         //         '%name%' => $Shipping->getId(),
        //         //         '%from%' => $from,
        //         //         '%to%' => $to,
        //         //     ])];
        //         // }

        //         // $OrderItems = $Order->getOrderItems();
        //         // foreach ($OrderItems as $OrderItem) {
        //             // $em = $this->getDoctrine()->getManager();
        //             // $OrderItem->setCusShippingStatusId(1)->setIsOrderCsvDownload(1);
        //             // $em->persist($OrderItem);
        //             // $em->flush();
        //         // }
        //         log_info('対応状況一括変更処理完了', [$OrderItem->getId()]);
        //     }
        // } catch (\Exception $e) {
        //     log_error('予期しないエラーです', [$e->getMessage()]);

        //     return $this->json(['status' => 'NG'], 500);
        // }

        return $this->json(array_merge(['status' => 'OK'], $result));
    }

    /**
     * Update to order status
     *
     * @Route("/%eccube_admin_route%/cus/option/change", name="custom_option_change", methods={"POST"})
     *
     * @param Request $request
    
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function CusOptionChange(Request $request)
    {
        $OrderItemId = $request->get('OrderItemId');
        $status = $request->get('status');
        $OrderItem = $this->orderItemRepository->find($OrderItemId);
        $OrderItem->setCusOrderStatusId($status);

        $em = $this->getDoctrine()->getManager();
        $em->persist($OrderItem);
        $em->flush();

        return new Response("OK");
    }

    /**
     * Update to cus shipping date
     *
     * @Route("/%eccube_admin_route%/cus/shipping_date/change", name="cus_shipping_date_change", methods={"POST"})
     *
     * @param Request $request
    
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function CusShippingDateChange(Request $request)
    {
        $cusShippingId = $request->get('cusShippingId');
        $changedDateString = $request->get('changedDate');
        $changedDate = new \DateTime($changedDateString);
        $CusShippingItem = $this->cusShippingRepository->find($cusShippingId);
        $CusShippingItem->setCusShippingDate($changedDate);       

        $em = $this->getDoctrine()->getManager();
        $em->persist($CusShippingItem);
        $em->flush();

        return new Response("OK");
    }

    /**
     * Update to Tracking number.
     *
     * @Route("/%eccube_admin_route%/shipping/{id}/tracking_number", requirements={"id" = "\d+"}, name="admin_shipping_update_tracking_number", methods={"PUT"})
     *
     * @param Request $request
     * @param Shipping $shipping
     *
     * @return Response
     */
    public function updateTrackingNumber(Request $request, Shipping $shipping)
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json(['status' => 'NG'], 400);
        }

        $trackingNumber = mb_convert_kana($request->get('tracking_number'), 'a', 'utf-8');
        /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $errors */
        $errors = $this->validator->validate(
            $trackingNumber,
            [
                new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                new Assert\Regex(
                    ['pattern' => '/^[0-9a-zA-Z-]+$/u', 'message' => trans('admin.order.tracking_number_error')]
                ),
            ]
        );

        if ($errors->count() != 0) {
            log_info('送り状番号入力チェックエラー');
            $messages = [];
            /** @var \Symfony\Component\Validator\ConstraintViolationInterface $error */
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }

            return $this->json(['status' => 'NG', 'messages' => $messages], 400);
        }

        try {
            $shipping->setTrackingNumber($trackingNumber);
            $this->entityManager->flush($shipping);
            log_info('送り状番号変更処理完了', [$shipping->getId()]);
            $message = ['status' => 'OK', 'shipping_id' => $shipping->getId(), 'tracking_number' => $trackingNumber];

            return $this->json($message);
        } catch (\Exception $e) {
            log_error('予期しないエラー', [$e->getMessage()]);

            return $this->json(['status' => 'NG'], 500);
        }
    }

    /**
     * @Route("/%eccube_admin_route%/order/export/pdf", name="admin_order_export_pdf")
     * @Template("@admin/Order/order_pdf.twig")
     *
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    public function exportPdf(Request $request)
    {
        // requestから出荷番号IDの一覧を取得する.
        $ids = $request->get('ids', []);

        if (count($ids) == 0) {
            $this->addError('admin.order.delivery_note_parameter_error', 'admin');
            log_info('The Order cannot found!');

            return $this->redirectToRoute('admin_order');
        }

        /** @var OrderPdf $OrderPdf */
        $OrderPdf = $this->orderPdfRepository->find($this->getUser());

        if (!$OrderPdf) {
            $OrderPdf = new OrderPdf();
            $OrderPdf
                ->setTitle(trans('admin.order.delivery_note_title__default'))
                ->setMessage1(trans('admin.order.delivery_note_message__default1'))
                ->setMessage2(trans('admin.order.delivery_note_message__default2'))
                ->setMessage3(trans('admin.order.delivery_note_message__default3'));
        }

        /**
         * @var FormBuilder
         */
        $builder = $this->formFactory->createBuilder(OrderPdfType::class, $OrderPdf);

        /* @var \Symfony\Component\Form\Form $form */
        $form = $builder->getForm();

        // Formへの設定
        $form->get('ids')->setData(implode(',', $ids));

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/order/export/pdf/download", name="admin_order_pdf_download")
     * @Template("@admin/Order/order_pdf.twig")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportPdfDownload(Request $request, OrderPdfService $orderPdfService)
    {
        /**
         * @var FormBuilder
         */
        $builder = $this->formFactory->createBuilder(OrderPdfType::class);

        /* @var \Symfony\Component\Form\Form $form */
        $form = $builder->getForm();
        $form->handleRequest($request);

        // Validation
        if (!$form->isValid()) {
            log_info('The parameter is invalid!');

            return $this->render('@admin/Order/order_pdf.twig', [
                'form' => $form->createView(),
            ]);
        }

        $arrData = $form->getData();

        // 購入情報からPDFを作成する
        $status = $orderPdfService->makePdf($arrData);

        // 異常終了した場合の処理
        if (!$status) {
            $this->addError('admin.order.export.pdf.download.failure', 'admin');
            log_info('Unable to create pdf files! Process have problems!');

            return $this->render('@admin/Order/order_pdf.twig', [
                'form' => $form->createView(),
            ]);
        }

        // ダウンロードする
        $response = new Response(
            $orderPdfService->outputPdf(),
            200,
            ['content-type' => 'application/pdf']
        );

        $downloadKind = $form->get('download_kind')->getData();

        // レスポンスヘッダーにContent-Dispositionをセットし、ファイル名を指定
        if ($downloadKind == 1) {
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $orderPdfService->getPdfFileName() . '"');
        } else {
            $response->headers->set('Content-Disposition', 'inline; filename="' . $orderPdfService->getPdfFileName() . '"');
        }

        log_info('OrderPdf download success!', ['Order ID' => implode(',', $request->get('ids', []))]);

        $isDefault = isset($arrData['default']) ? $arrData['default'] : false;
        if ($isDefault) {
            // Save input to DB
            $arrData['admin'] = $this->getUser();
            $this->orderPdfRepository->save($arrData);
        }

        return $response;
    }
}
