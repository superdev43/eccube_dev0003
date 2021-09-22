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

namespace Plugin\CustomShipping\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Repository\PageRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CusHelpController extends \Eccube\Controller\AbstractController
{

    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * HelpController constructor.
     */
    public function __construct(PageRepository $pageRepository)
    {
        $this->pageRepository = $pageRepository;

    }

    /**
     * 特定商取引法.
     *
     * @Route("/help/tradelaw", name="help_tradelaw")
     * @Template("Help/tradelaw.twig")
     */
    public function tradelaw(Request $request)
    {
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

            return [];
        }
    }

    /**
     * ご利用ガイド.
     *
     * @Route("/guide", name="help_guide")
     * @Template("Help/guide.twig")
     */
    public function guide(Request $request)
    {
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

            return [];
        }
    }

    /**
     * 当サイトについて.
     *
     * @Route("/help/about", name="help_about")
     * @Template("Help/about.twig")
     */
    public function about(Request $request)
    {        
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

            return [];
        }
    }

    /**
     * プライバシーポリシー.
     *
     * @Route("/help/privacy", name="help_privacy")
     * @Template("Help/privacy.twig")
     */
    public function privacy(Request $request)
    {
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

            return [];
        }
    }

    /**
     * 利用規約.
     *
     * @Route("/help/agreement", name="help_agreement")
     * @Template("Help/agreement.twig")
     */
    public function agreement(Request $request)
    {
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

            return [];
        }
    }
}
