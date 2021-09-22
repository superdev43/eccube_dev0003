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

namespace Plugin\CustomShipping\Controller\Admin;

use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\ExportCsvRow;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\OrderPdf;
use Eccube\Entity\Shipping;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Admin\OrderPdfType;
use Eccube\Form\Type\Admin\SearchOrderType;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Repository\Master\ProductStatusRepository;
use Eccube\Repository\Master\SexRepository;
use Eccube\Repository\OrderPdfRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\OrderItemRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\ProductStockRepository;
use Eccube\Service\CsvExportService;
use Eccube\Service\MailService;
use Plugin\CustomShipping\Service\OrderPdfService;
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
use Plugin\CustomShipping\Entity\CusShipping;
use Plugin\CustomShipping\Repository\CusShippingRepository;

class OrderController extends AbstractController
{
    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var CusShipping
     */
    protected $cusShipping;

    /**
     * @var CusShippingRepository
     */
    protected $cusShippingRepository;

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

    /**
     * @var OrderItemRepository
     */
    protected $orderItemRepository;

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
     * @param ProductRepository $productRepository ;
     * @param CusShipping $cusShipping;
     * @param CusShippingRepository $cusShippingRepository;
     * @param OrderItemRepository $orderItemRepository;
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
        ProductRepository $productRepository,
        CusShipping $cusShipping,
        CusShippingRepository $cusShippingRepository,
        OrderItemRepository $orderItemRepository

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
        $this->productRepository = $productRepository;
        $this->cusShipping = $cusShipping;
        $this->cusShippingRepository = $cusShippingRepository;
        $this->orderItemRepository = $orderItemRepository;
    }



    /**
     * @Route("/%eccube_admin_route%/cus/order/export/pdf/{id}", name="custom_order_export_pdf")
     * @Template("@CustomShipping/admin/order_pdf.twig")
     *
     * @param Request $request
     * @param CusShipping $cusShipping
     *
     * @return array|RedirectResponse
     */
    public function exportPdf(Request $request, CusShipping $cusShipping)
    {
        
        
        // $Product = $this->productRepository->find(1);
        // $Product->shipping_charge= 1000;
        // $this->entityManager->persist($Product);
        // $this->entityManager->flush();

        // requestから出荷番号IDの一覧を取得する.
        $orderId = $this->orderItemRepository->findBy(['cus_shipping_id'=> $cusShipping->getId()])[0]->getOrderId();
        $orderItemes = $this->orderItemRepository->findBy(['cus_shipping_id'=> $cusShipping->getId()]);
        $orderItemesIds = [];

        foreach($orderItemes as $item){
            $orderItemesIds[]=$item->getId();
        }    
        
        if ($orderItemesIds[0] == "") {
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
        $form->get('ids')->setData(implode(',', [$cusShipping->getId()]));
        return [
            'items' => implode(',', $orderItemesIds),
            'form' => $form->createView(),
        ];
    }


    /**
     * @Route("/%eccube_admin_route%/cus/order/export/pdf", name="multi_custom_order_export_pdf")
     * @Template("@CustomShipping/admin/order_pdf.twig")
     *
     * @param Request $request
     * @param CusShipping $cusShipping
     *
     * @return array|RedirectResponse
     */
    public function multi_exportPdf(Request $request)
    {
        $ids = $request->get('shipping-check', []);
        $orderItemsIds = [];
        $mapFunction = function($v){
            return $v->getId();
        };
        foreach($ids as $cusShippingId){
            $orderItemsIds[] = array_map($mapFunction,$this->orderItemRepository->findBy(['cus_shipping_id'=>$cusShippingId]));
        }
       
            
        
        // $Product = $this->productRepository->find(1);
        // $Product->shipping_charge= 1000;
        // $this->entityManager->persist($Product);
        // $this->entityManager->flush();

        // requestから出荷番号IDの一覧を取得する.
        // $orderId = $this->orderItemRepository->findBy(['cus_shipping_id'=> $cusShipping->getId()])[0]->getOrderId();
        // $orderItemes = $this->orderItemRepository->findBy(['cus_shipping_id'=> $cusShipping->getId()]);
        // $orderItemesIds = [];

        // foreach($orderItemes as $item){
        //     $orderItemesIds[]=$item->getId();
        // }    
        
        // if ($orderItemesIds[0] == "") {
        //     return $this->redirectToRoute('admin_order');
        // }

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
            // 'items' => implode(',', $orderItemsIds),
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/cus/export/pdf/download", name="custom_order_pdf_download")
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

        $arrData = $form->getData();
        
        $arrData['itemIds'] = explode(',', $_POST['items']);
        
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
