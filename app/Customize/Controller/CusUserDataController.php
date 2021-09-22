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

namespace Customize\Controller;

use Customize\Service\ProductHistory\ProductCollection;
use Eccube\Entity\Page;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\Master\DeviceTypeRepository;
use Eccube\Repository\PageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CusUserDataController extends  AbstractController
{
    const COOKIE_NAME = 'product_history';

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * @var DeviceTypeRepository
     */
    protected $deviceTypeRepository;

    /**
     * UserDataController constructor.
     *
     * @param PageRepository $pageRepository
     * @param DeviceTypeRepository $deviceTypeRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(
        ProductRepository $productRepository,
        PageRepository $pageRepository,
        DeviceTypeRepository $deviceTypeRepository
    ) {
        $this->productRepository = $productRepository;
        $this->pageRepository = $pageRepository;
        $this->deviceTypeRepository = $deviceTypeRepository;
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
     * @Route("/%eccube_user_data_route%/{route}", name="user_data", requirements={"route": "([0-9a-zA-Z_\-]+\/?)+(?<!\/)"})
     */
    public function index(Request $request, $route)
    {
        
        $Page = $this->pageRepository->findOneBy(
            [
                'url' => $route,
                'edit_type' => Page::EDIT_TYPE_USER,
            ]
        );
        if (null === $Page) {
            throw new NotFoundHttpException();
        }
        
        if ($this->getUser() == null && $Page->getIsPremMember() == 1) {
            return $this->redirectToRoute('entry_prem');
        }else{
            

            $file = sprintf('@user_data/%s.twig', $Page->getFileName());

            $event = new EventArgs(
                [
                    'Page' => $Page,
                    'file' => $file,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::FRONT_USER_DATA_INDEX_INITIALIZE, $event);

            return $this->render($file);
        }
       
    }
      /**
     * 商品一覧画面.
     *
     * @Route("/mode={route}", name="user_data" )
     */
    public function mode_page_f(Request $request, String $route)
    {
        $cookie_name = "product_history";
        if(isset($_COOKIE[$cookie_name])){

            $productHistoryIds = json_decode($_COOKIE[$cookie_name],true);
            $ProductHistory = [];
    
            foreach($productHistoryIds as $id){
                $ProductHistory[] = $this->productRepository->find($id);
            }
        }
        $Page = $this->pageRepository->findOneBy(
            [
                'url' => $route,
                'edit_type' => Page::EDIT_TYPE_USER,
            ]
        );

        if (null === $Page) {
            throw new NotFoundHttpException();
        }
        
        $file = sprintf('@user_data/%s.twig',$Page->getFileName());

        $event = new EventArgs(
            [
                'Page'=>$Page,
                'file'=>$file,               
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_USER_DATA_INDEX_INITIALIZE, $event);
        if(isset($_COOKIE[$cookie_name])){

            return $this->render($file, [
                'productHistory'=>$ProductHistory
            ]);      
        }else{

            return $this->render($file);      
        }
        
    }


}
