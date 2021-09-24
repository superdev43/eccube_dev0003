<?php

namespace Plugin\CustomShipping\Controller\Admin;

use DateTime;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Plugin\CustomShipping\Form\Type\Admin\ConfigType;
use Plugin\CustomShipping\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\OrderItemRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\OrderHelper;
use Eccube\Entity\Shipping;
use Eccube\Entity\Product;
use Plugin\CustomShipping\Repository\CusShippingRepository;
use Plugin\CustomShipping\Entity\CusShipping;
use Eccube\Entity\BaseInfo;

use Eccube\Repository\BaseInfoRepository;

class ConfigController extends AbstractController
{

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var \Twig\Environment
     */
    protected $twig;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var CusShippingRepository
     */
    protected $cusshippingRepository;

    /**
     * @var CusShipping
     */
    protected $cusShipping;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;
    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderItemRepository
     */
    protected $orderItemRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        \Swift_Mailer $mailer,
        ConfigRepository $configRepository,
        CartService $cartService,
        MailService $mailService,
        OrderRepository $orderRepository,
        OrderItemRepository $orderItemRepository,
        ProductRepository $productRepository,
        OrderHelper $orderHelper,
        CusShippingRepository $cusshippingRepository,
        \Twig\Environment $twig,
        BaseInfoRepository $baseInfoRepository,
        CusShipping $cusShipping
    ) {
        $this->mailer = $mailer;
        $this->configRepository = $configRepository;
        $this->cartService = $cartService;
        $this->mailService = $mailService;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->productRepository = $productRepository;
        $this->orderHelper = $orderHelper;
        $this->cusshippingRepository = $cusshippingRepository;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->twig = $twig;
        $this->cusShipping = $cusShipping;
    }


    /**
     * @Route("/%eccube_admin_route%/custom_shipping/config/preview_notify_mail/{id}", name="custom_shipping_admin_config_pre_notify_mail")
     * @Template("@CustomShipping/admin/config.twig")
     */

    public function cus_previewShippingNotifyMail(Request $request, CusShipping $cusShipping)
    {
        
        $OrderItems = $this->orderItemRepository->findBy(['cus_shipping_id' => $cusShipping->getId()]);

        /** @var $Order \Eccube\Entity\Order */
        $Order = $OrderItems[0]->getOrder();
        $Shipping = $Order->getShippings()[0];

        // // $Order->addItem();
        $tracking_number = $OrderItems[0]->getCusShipping()->getCusTrackNumber() ? $OrderItems[0]->getCusShipping()->getCusTrackNumber() : "";
        // $cus_shipping_charge = $request->get('cus_shipping_charge');
        $fileName = '@CustomShipping/mail/shipping_notify.twig';

        $product_sub_total = 0;
        foreach($OrderItems as $item){
            $product_sub_total += $item->getProductClass()->getPrice02() * $item->getQuantity();
        }

        return $this->render($fileName, [
            'Order' => $Order,
            'Shipping' => $Shipping,
            'OrderItems' => $OrderItems,
            'tracking_number' => $tracking_number,
            'ProductSubTotal' => $product_sub_total
        ]);
    }

    /**
     * @Route("/%eccube_admin_route%/custom_shipping/config/notify_mail/{id}", name="custom_shipping_admin_config_notify_mail", methods={"PUT"})
     *
     * @param CusShipping $Shipping
     *
     * @return JsonResponse
     *
     * @throws \Twig_Error
     */
    public function cus_shippingNotifyMail(Request $request, CusShipping $cusShipping)
    {
       
        $OrderItems = $this->orderItemRepository->findBy(['cus_shipping_id' => $cusShipping->getId()]);
        
        

        /** @var $Order \Eccube\Entity\Order */
        $Order = $OrderItems[0]->getOrder();
        $Shipping = $Order->getShippings()[0];

        // // $Order->addItem();
        $tracking_number = $OrderItems[0]->getCusShipping()->getCusTrackNumber() ? $OrderItems[0]->getCusShipping()->getCusTrackNumber() : "";
        
        // $cus_shipping_charge = $request->get('cus_shipping_charge');

        $fileName = '@CustomShipping/mail/shipping_notify.twig';
        $product_sub_total = 0;
        foreach($OrderItems as $item){
            $product_sub_total += $item->getProductClass()->getPrice02() * $item->getQuantity();
        }

        $htmlBody = $this->renderView($fileName, [
            'Order' => $Order,
            'Shipping' => $Shipping,
            'OrderItems' => $OrderItems,
            'tracking_number' => $tracking_number,
            'ProductSubTotal' => $product_sub_total           
        ]);

       
        $message = (new \Swift_Message())
            ->setSubject('[' . $this->BaseInfo->getShopName() . '] ' . '商品出荷のお知らせ')
            ->setFrom([$this->BaseInfo->getEmail01() => $this->BaseInfo->getShopName()])
            ->setTo($Order->getEmail())
            ->setBcc($this->BaseInfo->getEmail01())
            ->setReplyTo($this->BaseInfo->getEmail03())
            ->setReturnPath($this->BaseInfo->getEmail04());

        $message
            ->setContentType('text/plain; charset=UTF-8')
            ->setBody($htmlBody, 'text/plain')
            ->addPart($htmlBody, 'text/html');
        

        $this->mailer->send($message);     

        foreach($OrderItems as $item){
            $item->setIsMailSent(1);
            $em = $this->getDoctrine()->getManager();
            $em->persist($item);
            $em->flush();
        }

        

        // $this->shippingRepository->save($Shipping);

        // $this->entityManager->flush();

        return $this->json([
            'mail' => true,
            'shipped' => false,
        ]);
    }


    /**
     * @Route("/%eccube_admin_route%/custom_shipping/config/get_status/{id}", name="custom_shipping_admin_config_get_status")
     */
    public function get_staus(Request $request, OrderItem $OrderItem)
    {
        return $this->json([
            'status' => false
        ]);
    }
}
