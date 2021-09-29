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

namespace Customize\EventSubscriber;


use Customize\Service\ProductHistory\ProductCollection;
use Eccube\Common\EccubeConfig;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\ProductRepository;
use Eccube\Request\Context;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\FilterControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class ProductHistoryEventSubscriber
 * @package Customize\EventSubscriber
 */
class ProductHistoryEventSubscriber implements EventSubscriberInterface
{
    const COOKIE_NAME = 'product_history';

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        ProductRepository $productRepository,
        EccubeConfig $eccubeConfig,
        Context $context,
        EventDispatcherInterface $eventDispatcher
    )
    {
        $this->productRepository = $productRepository;
        $this->eccubeConfig = $eccubeConfig;
        $this->context = $context;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
            KernelEvents::CONTROLLER_ARGUMENTS => 'onKernelControllerArguments'
        ];
    }

    /**
     * 商品詳細ページにアクセスしたら商品IDをCookieに保存
     *
     * @param FilterResponseEvent $event
     * @throws \Exception
     */
    public function onKernelResponse(FilterResponseEvent $event): void
    {
        if (false === $event->isMasterRequest()) {
            return;
        }
        
        if ($event->getRequest()->get('_route') !== 'homepage') {
            return;
        }
        if (isset($_GET['pid'])) {
            $product_id = $_GET['pid'];
            $product = $this->productRepository->find($product_id);
            if (null === $product) {
                return;
            }

            $cookie = $this->getCookie($event);

            // 商品IDを追加
            $products = new ProductCollection($cookie, 12);
            $products->addProduct($product);

            // Cookie作成・更新
            $cookie = $this->createCookie($products);

            $response = $event->getResponse();
            $response->headers->setCookie($cookie);
            $event->setResponse($response);
        }
    }

    /**
     * チェックした商品をフロント側のすべてのTwigテンプレートにセット
     *
     * @param FilterControllerArgumentsEvent $event
     */
    public function onKernelControllerArguments(FilterControllerArgumentsEvent $event): void
    {
        if ($this->context->isAdmin()) {
            return;
        }

        if ($event->getRequest()->attributes->has('_template')) {
            $cookie = $this->getCookie($event);
            $template = $event->getRequest()->attributes->get('_template');
            $this->eventDispatcher->addListener($template->getTemplate(), function (TemplateEvent $templateEvent) use ($cookie) {

                $productHistory = [];
                $products = new ProductCollection($cookie);
                if ($products->count() > 0) {
                    foreach ($products as $product) {
                        if ($product = $this->productRepository->find($product)) {
                            $productHistory[] = $product;
                        }
                    }
                }
                $templateEvent->setParameter('productHistory', $productHistory);
            });
        }
    }

    /**
     * Cookie取得
     *
     * @param KernelEvent $event
     * @return array
     */
    private function getCookie(KernelEvent $event): array
    {
        $cookie = $event->getRequest()->cookies->get(self::COOKIE_NAME);
        return json_decode($cookie, true) ?? [];
    }

    /**
     * Cookie作成・更新
     *
     * @param ProductCollection $productCollection
     * @return Cookie
     * @throws \Exception
     */
    private function createCookie(ProductCollection $productCollection): Cookie
    {
        return new Cookie(
            self::COOKIE_NAME,
            json_encode($productCollection->toArray()),
            (new \DateTime())->modify('1 month'),
            $this->eccubeConfig['env(ECCUBE_COOKIE_PATH)']
        );
    }
}