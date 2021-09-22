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

namespace Plugin\ProductReview4\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Product;
use Plugin\ProductReview4\Entity\ProductReview;
use Plugin\ProductReview4\Entity\ProductReviewStatus;
use Plugin\ProductReview4\Form\Type\ProductReviewType;
use Plugin\ProductReview4\Repository\ProductReviewRepository;
use Plugin\ProductReview4\Repository\ProductReviewConfigRepository;
use Plugin\ProductReview4\Repository\ProductReviewStatusRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ProductReviewController front.
 */
class ProductReviewController extends AbstractController
{

     /**
     * @var ProductReviewConfigRepository
     */
    protected $productReviewConfigRepository;
    /**
     * @var ProductReviewStatusRepository
     */
    private $productReviewStatusRepository;

    /**
     * @var ProductReviewRepository
     */
    private $productReviewRepository;

    /**
     * ProductReviewController constructor.
     *
     * @param ProductReviewStatusRepository $productStatusRepository
     * @param ProductReviewRepository $productReviewRepository
     * @param ProductReviewConfigRepository $productReviewConfigRepository
     */
    public function __construct(
        ProductReviewConfigRepository $productReviewConfigRepository,
        ProductReviewStatusRepository $productStatusRepository,
        ProductReviewRepository $productReviewRepository
    ) {
        $this->productReviewConfigRepository = $productReviewConfigRepository;
        $this->productReviewStatusRepository = $productStatusRepository;
        $this->productReviewRepository = $productReviewRepository;
    }

    /**
     * @Route("/product_review/{id}/review", name="product_review_index", requirements={"id" = "\d+"})
     * @Route("/product_review/{id}/review", name="product_review_confirm", requirements={"id" = "\d+"})
     *
     * @param Request $request
     * @param Product $Product
     *
     * @return RedirectResponse|Response
     */
    public function index(Request $request, Product $Product)
    {
        if (!$this->session->has('_security_admin') && $Product->getStatus()->getId() !== ProductStatus::DISPLAY_SHOW) {
            log_info('Product review', ['status' => 'Not permission']);

            throw new NotFoundHttpException();
        }

        $ProductReview = new ProductReview();
        $form = $this->createForm(ProductReviewType::class, $ProductReview);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var $ProductReview ProductReview */
            $ProductReview = $form->getData();

            switch ($request->get('mode')) {
                case 'confirm':
                    log_info('Product review config confirm');

                    return $this->render('ProductReview4/Resource/template/default/confirm.twig', [
                        'form' => $form->createView(),
                        'Product' => $Product,
                        'ProductReview' => $ProductReview,
                    ]);
                    break;

                case 'complete':
                    log_info('Product review complete');
                    if ($this->isGranted('ROLE_USER')) {
                        $Customer = $this->getUser();
                        $ProductReview->setCustomer($Customer);
                    }
                    $ProductReview->setProduct($Product);
                    $ProductReview->setStatus($this->productReviewStatusRepository->find(ProductReviewStatus::HIDE));
                    $this->entityManager->persist($ProductReview);
                    $this->entityManager->flush($ProductReview);

                    log_info('Product review complete', ['id' => $Product->getId()]);

                    return $this->redirectToRoute('product_review_complete', ['id' => $Product->getId()]);
                    break;

                case 'back':
                    // 確認画面から投稿画面へ戻る
                    break;

                default:
                    // do nothing
                    break;
            }
        }

        return $this->render('ProductReview4/Resource/template/default/index.twig', [
            'Product' => $Product,
            'ProductReview' => $ProductReview,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/product_review/{id}/review_list", name="product_review_list", requirements={"id" = "\d+"})
     *
     * @param Request $request
     * @param Product $Product
     *
     * @return RedirectResponse|Response
     */
    public function productReviewList(Request $request, Product $Product)
    {
        $Config = $this->productReviewConfigRepository->get();
        $ProductReviews = $this->productReviewRepository->findBy(['Status' => ProductReviewStatus::SHOW, 'Product' => $Product], ['id' => 'DESC'], $Config->getReviewMax());
        $rate = $this->productReviewRepository->getAvgAll($Product);
        $count = intval($rate['review_count']);
        $avg = round($rate['recommend_avg']);
        return $this->render('ProductReview4/Resource/template/default/review_list.twig', [
            'ProductReviews' => $ProductReviews, 
            'Product'=>$Product,
            'ProductReviewAvg' => $avg,
            'ProductReviewCount' => $count       
        ]);
    }

    /**
     * Complete.
     *
     * @Route("/product_review/{id}/complete", name="product_review_complete", requirements={"id" = "\d+"})
     * @Template("ProductReview4/Resource/template/default/complete.twig")
     *
     * @param $id
     *
     * @return array
     */
    public function complete($id)
    {
        return ['id' => $id];
    }

    /**
     * ページ管理表示用のダミールーティング.
     *
     * @Route("/product_review/display", name="product_review_display")
     */
    public function display()
    {
        return new Response();
    }
    
}
