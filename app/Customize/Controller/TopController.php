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

use Eccube\Entity\BaseInfo;
use Eccube\Entity\Page;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Form\Type\SearchProductType;
use Eccube\Controller\AbstractController;
use Eccube\Form\Type\Master\ProductListMaxType;
use Eccube\Form\Type\Master\ProductListOrderByType;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Knp\Component\Pager\Paginator;
use Eccube\Entity\Product;
use Eccube\Entity\Master\ProductStatus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Eccube\Repository\PageRepository;
use Eccube\Repository\ProductRepository;
use Eccube\Form\Type\AddCartType;
use Doctrine\ORM\Query\Expr\Join;
use Eccube\Entity\ProductClass;
use Eccube\Event\EventArgs;
use Eccube\Event\EccubeEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Eccube\Repository\CustomerFavoriteProductRepository;
use Plugin\ProductReview4\Repository\ProductReviewRepository;
use Eccube\Repository\ProductCategoryRepository;

class TopController extends AbstractController
{
    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var CustomerFavoriteProductRepository
     */
    protected $customerFavoriteProductRepository;


    /**
     * @var ProductCategoryRepository
     */
    protected $productCategoryRepository;

    /**
     * @var ProductReviewRepository
     */
    protected $productReviewRepository;

    /**
     * コンテナ
     */
    protected $container;
    
    /**
     * 汎用処理用ユーティリティ
     */
    protected $util;

    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    private $title = '';

    public function __construct(BaseInfoRepository $baseInfoRepository,ProductCategoryRepository $productCategoryRepository, ProductReviewRepository $productReviewRepository,CustomerFavoriteProductRepository $customerFavoriteProductRepository,ContainerInterface $container,PageRepository $pageRepository, ProductRepository $productRepository)
    {
        $this->pageRepository = $pageRepository;
        $this->productRepository = $productRepository;
        $this->container = $container;
        $this->util = $container->get('vt4g_plugin.service.util');
        $this->customerFavoriteProductRepository = $customerFavoriteProductRepository;
        $this->productReviewRepository = $productReviewRepository;
        $this->productCategoryRepository = $productCategoryRepository;
        $this->BaseInfo = $baseInfoRepository->get();
    }

    /**
     * @Route("/", name="homepage")
     * @Template("index.twig")
     */
    public function cus_index(Request $request,Paginator $paginator)
    {
        if(isset($_GET['mode'])){
            if($_GET['mode'] == "cate"){
                $currentRoute = $request->attributes->get('_route');

                $Page = $this->pageRepository->findOneBy(
                    [
                        'url' => $currentRoute,
                        // 'edit_type' => Page::EDIT_TYPE_USER,
                    ]
                );

                if (null === $Page) {
                    throw new NotFoundHttpException();
                }
                if ($this->getUser() == null && $Page->getIsPremMember() == 1) {
                    return $this->redirectToRoute('entry_prem');
                }else{

                    $Customer = $this->getUser();
                    // Doctrine SQLFilter
                    if ($this->BaseInfo->isOptionNostockHidden()) {
                        $this->entityManager->getFilters()->enable('option_nostock_hidden');
                    }
            
                    // handleRequestは空のqueryの場合は無視するため
                    if ($request->getMethod() === 'GET') {
                        $request->query->set('pageno', $request->query->get('pageno', ''));
                    }
            
                    // searchForm
                    /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
                    $builder = $this->formFactory->createNamedBuilder('', SearchProductType::class);
            
                    if ($request->getMethod() === 'GET') {
                        $builder->setMethod('GET');
                    }
            
                    $event = new EventArgs(
                        [
                            'builder' => $builder,
                        ],
                        $request
                    );
                    $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_INITIALIZE, $event);
            
                    /* @var $searchForm \Symfony\Component\Form\FormInterface */
                    $searchForm = $builder->getForm();
            
                    $searchForm->handleRequest($request);
            
                    // paginator
                    $searchData = $searchForm->getData();
                    $qb = $this->productRepository->getQueryBuilderBySearchData($searchData);
                    if(isset($_GET['low_price']) && isset($_GET['high_price'])){
                        $low_price = $_GET['low_price'];
                        $high_price = $_GET['high_price'];
                        $qb->innerJoin(ProductClass::class, 'pcl', Join::WITH, 'p.id = pcl.Product')
                        ->where('pcl.price02 >= :low_price ')->setParameter('low_price', $low_price)
                        ->andWhere('pcl.price02 <= :high_price ')->setParameter('high_price', $high_price);
                    }
                    
                    $event = new EventArgs(
                        [
                            'searchData' => $searchData,
                            'qb' => $qb,
                        ],
                        $request
                    );
                    $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_SEARCH, $event);
                    $searchData = $event->getArgument('searchData');
            
                    $query = $qb->getQuery()
                        ->useResultCache(true, $this->eccubeConfig['eccube_result_cache_lifetime_short']);
            
                    /** @var SlidingPagination $pagination */
                    $pagination = $paginator->paginate(
                        $query,
                        !empty($searchData['pageno']) ? $searchData['pageno'] : 1,
                        !empty($searchData['disp_number']) ? $searchData['disp_number']->getId() : $this->productListMaxRepository->findOneBy([], ['sort_no' => 'ASC'])->getId()
                    );           
                    
            
                    $ids = [];
                    foreach ($pagination as $Product) {
                        $ids[] = $Product->getId();
                    }
                    $ProductsAndClassCategories = $this->productRepository->findProductsWithSortedClassCategories($ids, 'p.id');
                    
                    // addCart form
                    $forms = [];
                    foreach ($pagination as $Product) {
                        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
                        $builder = $this->formFactory->createNamedBuilder(
                            '',
                            AddCartType::class,
                            null,
                            [
                                'product' => $ProductsAndClassCategories[$Product->getId()],
                                'allow_extra_fields' => true,
                            ]
                        );
                        $addCartForm = $builder->getForm();
            
                        $forms[$Product->getId()] = $addCartForm->createView();
                    }
            
                    // 表示件数
                    $builder = $this->formFactory->createNamedBuilder(
                        'disp_number',
                        ProductListMaxType::class,
                        null,
                        [
                            'required' => false,
                            'allow_extra_fields' => true,
                        ]
                    );
                    if ($request->getMethod() === 'GET') {
                        $builder->setMethod('GET');
                    }
            
                    $event = new EventArgs(
                        [
                            'builder' => $builder,
                        ],
                        $request
                    );
                    $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_DISP, $event);
            
                    $dispNumberForm = $builder->getForm();
            
                    $dispNumberForm->handleRequest($request);
            
                    // ソート順
                    $builder = $this->formFactory->createNamedBuilder(
                        'orderby',
                        ProductListOrderByType::class,
                        null,
                        [
                            'required' => false,
                            'allow_extra_fields' => true,
                        ]
                    );
                    if ($request->getMethod() === 'GET') {
                        $builder->setMethod('GET');
                    }
            
                    $event = new EventArgs(
                        [
                            'builder' => $builder,
                        ],
                        $request
                    );
                    $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_INDEX_ORDER, $event);
            
                    $orderByForm = $builder->getForm();
            
                    $orderByForm->handleRequest($request);
                    // var_export($searchForm->get('category_id')->getData()->getId());die;
                    $Category = $searchForm->get('category_id')->getData();
                    if(isset($_GET['low_price']) && isset($_GET['high_price'])){
                        $low_price = $_GET['low_price'];
                        $high_price = $_GET['high_price'];
                        return $this->render('Product/list.twig', [
                            'subtitle' => $this->getPageTitle($searchData),
                            'pagination' => $pagination,
                            'LowPrice' => $low_price,
                            'HighPrice' => $high_price,
                            'search_form' => $searchForm->createView(),
                            'disp_number_form' => $dispNumberForm->createView(),
                            'order_by_form' => $orderByForm->createView(),
                            'forms' => $forms,
                            'Category' => $Category,
                            'Customer' => $Customer
                        ]);
                    }else{

                        return $this->render('Product/list.twig', [
                            'subtitle' => $this->getPageTitle($searchData),
                            'pagination' => $pagination,
                            'search_form' => $searchForm->createView(),
                            'disp_number_form' => $dispNumberForm->createView(),
                            'order_by_form' => $orderByForm->createView(),
                            'forms' => $forms,
                            'Category' => $Category,
                            'Customer' => $Customer
                        ]);
                    }
                }
            }else{
                $route = $_GET['mode'];
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
        else if(isset($_GET['pid'])){
            $ProductId = $_GET['pid'];
            $currentRoute = $request->attributes->get('_route');

            $Page = $this->pageRepository->findOneBy(
                [
                    'url' => $currentRoute,
                    // 'edit_type' => Page::EDIT_TYPE_USER,
                ]
            );
            $Product = $this->productRepository->find($ProductId);
            

            if (null === $Page) {
                throw new NotFoundHttpException();
            }
            if ($this->getUser() == null && $Page->getIsPremMember() == 1) {
                return $this->redirectToRoute('entry_prem');
            }else{
                
                $pluginSetting = $this->util->getPluginSetting();
                if (!$this->checkVisibility($Product)) {
                    throw new NotFoundHttpException();
                }
        
        
                $builder = $this->formFactory->createNamedBuilder(
                    '',
                    AddCartType::class,
                    null,
                    [
                        'product' => $Product,
                        'id_add_product_id' => false,
                    ]
                );
        
                $event = new EventArgs(
                    [
                        'builder' => $builder,
                        'Product' => $Product,
                    ],
                    $request
                );
                $this->eventDispatcher->dispatch(EccubeEvents::FRONT_PRODUCT_DETAIL_INITIALIZE, $event);
        
                $is_favorite = false;
                $Customer = $this->getUser();
                if ($this->isGranted('ROLE_USER')) {
                    $is_favorite = $this->customerFavoriteProductRepository->isFavorite($Customer, $Product);
                }
                $rate = $this->productReviewRepository->getAvgAll($Product);
                $count = intval($rate['review_count']);
                $CagegoryIdOfProduct = $this->productCategoryRepository->findOneBy([
                    'Product'=>$Product->getId()
                ])->getCategory()->getId();
                
                return $this->render('Product/detail.twig',[
                    'title' => $this->title,
                    'subtitle' => $Product->getName(),
                    'form' => $builder->getForm()->createView(),
                    'Product' => $Product,
                    'is_favorite' => $is_favorite,
                    'Customer' => $Customer,
                    'tokenApiKey' => $pluginSetting['token_api_key'],
                    'ProductReviewCount' => $count,
                    'categoryId' => $CagegoryIdOfProduct
                ]);

            }
            
        }else{

            return [];
        }
    }

    /**
     * 閲覧可能な商品かどうかを判定
     *
     * @param Product $Product
     *
     * @return boolean 閲覧可能な場合はtrue
     */
    protected function checkVisibility(Product $Product)
    {
        $is_admin = $this->session->has('_security_admin');

        // 管理ユーザの場合はステータスやオプションにかかわらず閲覧可能.
        if (!$is_admin) {
            // 在庫なし商品の非表示オプションが有効な場合.
            // if ($this->BaseInfo->isOptionNostockHidden()) {
            //     if (!$Product->getStockFind()) {
            //         return false;
            //     }
            // }
            // 公開ステータスでない商品は表示しない.
            if ($Product->getStatus()->getId() !== ProductStatus::DISPLAY_SHOW) {
                return false;
            }
        }

        return true;
    }
    protected function getPageTitle($searchData)
    {
        if (isset($searchData['name']) && !empty($searchData['name'])) {
            return trans('front.product.search_result');
        } elseif (isset($searchData['category_id']) && $searchData['category_id']) {
            return $searchData['category_id']->getName();
        } else {
            return trans('front.product.all_products');
        }
    }
}
