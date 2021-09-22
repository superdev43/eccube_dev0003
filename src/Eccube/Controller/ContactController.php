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

namespace Eccube\Controller;

use Eccube\Entity\Customer;
use Eccube\Entity\Product;
use Eccube\Repository\ProductRepository;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Front\ContactType;
use Eccube\Form\Type\Front\ToFriendType;
use Eccube\Service\MailService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Eccube\Repository\PageRepository;

class ContactController extends AbstractController
{

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * ContactController constructor.
     *
     * @param MailService $mailService
     * @param ProductRepository $productRepository
     */
    public function __construct(PageRepository $pageRepository,
        MailService $mailService,
        ProductRepository $productRepository,
        \Swift_Mailer $mailer)
    {
        $this->mailer = $mailer;
        $this->mailService = $mailService;
        $this->pageRepository = $pageRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * お問い合わせ画面.
     *
     * @Route("/contact/product/{id}", name="contact_product",requirements={"id" = "\d+"})
     * @Template("Contact/index.twig")
     */
    public function contact_product(Request $request, Product $Product)
    {
        
        // $currentRoute = $request->attributes->get('_route');

        // $Page = $this->pageRepository->findOneBy(
        //     [
        //         'url' => $currentRoute,
        //         // 'edit_type' => Page::EDIT_TYPE_USER,
        //     ]
        // );

        // if (null === $Page) {
        //     throw new NotFoundHttpException();
        // }
        // if ($this->getUser() == null && $Page->getIsPremMember() == 1) {
        //     return $this->redirectToRoute('entry_prem');
        // else

            $builder = $this->formFactory->createBuilder(ContactType::class);
    
            if ($this->isGranted('ROLE_USER')) {
                /** @var Customer $user */
                $user = $this->getUser();
                $builder->setData(
                    [
                        'name01' => $user->getName01(),
                        'name02' => $user->getName02(),
                        'kana01' => $user->getKana01(),
                        'kana02' => $user->getKana02(),
                        'postal_code' => $user->getPostalCode(),
                        'pref' => $user->getPref(),
                        'addr01' => $user->getAddr01(),
                        'addr02' => $user->getAddr02(),
                        'phone_number' => $user->getPhoneNumber(),
                        'email' => $user->getEmail(),
                    ]
                );
            }
    
            // FRONT_CONTACT_INDEX_INITIALIZE
            $event = new EventArgs(
                [
                    'builder' => $builder,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::FRONT_CONTACT_INDEX_INITIALIZE, $event);
    
            $form = $builder->getForm();
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                // var_export("dffdf");die;
                switch ($request->get('mode')) {
                    case 'confirm':
                        $form = $builder->getForm();
                        $form->handleRequest($request);
    
                        return $this->render('Contact/confirm.twig', [
                            'form' => $form->createView(),
                            'Product' => $Product
                        ]);
    
                    case 'complete':
    
                        $data = $form->getData();
    
                        $event = new EventArgs(
                            [
                                'form' => $form,
                                'data' => $data,
                                'Product' => $Product
                            ],
                            $request
                        );
                        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_CONTACT_INDEX_COMPLETE, $event);
    
                        $data = $event->getArgument('data');
    
                        // メール送信
                        $this->mailService->sendContactMail($data);
    
                        return $this->redirect($this->generateUrl('contact_complete'));
                }
            }
    
            return [
                'form' => $form->createView(),
                'Product' => $Product
            ];
        // }


    }

    /**
     * お問い合わせ画面.
     * 
     * @Route("/to/friend/product/{id}", name="intro_friend",requirements={"id" = "\d+"})
     * @Template("Contact/to_friend.twig")
     */
    public function intro_freind(Request $request, Product $Product)
    {
        $builder = $this->formFactory->createBuilder(ToFriendType::class);      
        
        if ($this->isGranted('ROLE_USER')) {
            /** @var Customer $user */
            $user = $this->getUser();
            $builder->setData(
                [
                    'name01' => $user->getName01(),
                    'name02' => $user->getName02(),
                    'kana01' => $user->getKana01(),
                    'kana02' => $user->getKana02(),
                    'postal_code' => $user->getPostalCode(),
                    'pref' => $user->getPref(),
                    'addr01' => $user->getAddr01(),
                    'addr02' => $user->getAddr02(),
                    'phone_number' => $user->getPhoneNumber(),
                    'email' => $user->getEmail(),
                ]
            );
        }

        // FRONT_CONTACT_INDEX_INITIALIZE
        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_CONTACT_INDEX_INITIALIZE, $event);

        $form = $builder->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $event = new EventArgs(
                [
                    'form' => $form,
                    'data' => $data                            
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::FRONT_CONTACT_INDEX_COMPLETE, $event);

            $data = $event->getArgument('data');

            // メール送信
            $fileName = 'Contact/to_friend_mail_html.twig';
            $htmlBody = $this->render($fileName, [
                    'data' => $data,
                    'Product' => $Product       
            ]);
            $message = (new \Swift_Message())
            ->setSubject($data['subject'])
            ->setFrom($data['youremail'])
            ->setTo($data['friendemail']);
            // ->setBcc($this->BaseInfo->getEmail01())
            // ->setReplyTo($this->BaseInfo->getEmail03())
            // ->setReturnPath($this->BaseInfo->getEmail04());

            $message
                ->setContentType('text/plain; charset=UTF-8')
                ->setBody($htmlBody, 'text/plain')
                ->addPart($htmlBody, 'text/html');
            

            $this->mailer->send($message);   
            // $this->mailService->sendContactMail($data);

            return $this->redirect($this->generateUrl('to_friend_complete'));
            
        }
       
        return [
            'form' => $form->createView(),
            'Product' => $Product
        ];


    }



    /**
     * お問い合わせ画面.
     *
     * @Route("/contact", name="contact")
     * @Template("Contact/index.twig")
     */
    public function index(Request $request)
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

            $builder = $this->formFactory->createBuilder(ContactType::class);
    
            if ($this->isGranted('ROLE_USER')) {
                /** @var Customer $user */
                $user = $this->getUser();
                $builder->setData(
                    [
                        'name01' => $user->getName01(),
                        'name02' => $user->getName02(),
                        'kana01' => $user->getKana01(),
                        'kana02' => $user->getKana02(),
                        'postal_code' => $user->getPostalCode(),
                        'pref' => $user->getPref(),
                        'addr01' => $user->getAddr01(),
                        'addr02' => $user->getAddr02(),
                        'phone_number' => $user->getPhoneNumber(),
                        'email' => $user->getEmail(),
                    ]
                );
            }
    
            // FRONT_CONTACT_INDEX_INITIALIZE
            $event = new EventArgs(
                [
                    'builder' => $builder,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::FRONT_CONTACT_INDEX_INITIALIZE, $event);
    
            $form = $builder->getForm();
            $form->handleRequest($request);
    
            if ($form->isSubmitted() && $form->isValid()) {
                // var_export("dffdf");die;
                switch ($request->get('mode')) {
                    case 'confirm':
                        $form = $builder->getForm();
                        $form->handleRequest($request);
    
                        return $this->render('Contact/confirm.twig', [
                            'form' => $form->createView(),
                        ]);
    
                    case 'complete':
    
                        $data = $form->getData();
    
                        $event = new EventArgs(
                            [
                                'form' => $form,
                                'data' => $data,
                            ],
                            $request
                        );
                        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_CONTACT_INDEX_COMPLETE, $event);
    
                        $data = $event->getArgument('data');
    
                        // メール送信
                        $this->mailService->sendContactMail($data);
    
                        return $this->redirect($this->generateUrl('contact_complete'));
                }
            }
    
            return [
                'form' => $form->createView(),
            ];
        }


    }

    /**
     * お問い合わせ完了画面.
     *
     * @Route("/contact/complete", name="contact_complete")
     * @Template("Contact/complete.twig")
     */
    public function complete(Request $request)
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
     * お問い合わせ完了画面.
     *
     * @Route("/to/friend/complete", name="to_friend_complete")
     * @Template("Contact/friend_complete.twig")
     */
    public function to_friend_complete(Request $request)
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
