<?php
/*
 * Copyright (c) 2018 VeriTrans Inc., a Digital Garage company. All rights reserved.
 * http://www.veritrans.co.jp/
 */

namespace Plugin\VeriTrans4G\Form\Type\Shopping;

use Eccube\Service\CartService;
use Eccube\Repository\OrderRepository;
use Plugin\VeriTrans4G\Repository\Vt4gPluginRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

/**
 * クレジットカード情報入力フォーム
 */
class PaymentAmazonPayType extends AbstractType
{
    /**
     * コンテナ
     */
    private $container;

    /**
     * エンティティーマネージャー
     */
    private $em;

    /**
     * VT用固定値配列
     */
    private $vt4gConst;

    /**
     * コンストラクタ
     *
     * @param  ContainerInterface   $container
     * @param  CartService          $cartService
     * @param  OrderRepository      $orderRepository
     * @param  Vt4gPluginRepository $vt4gPluginRepository
     * @return void
     */
    public function __construct(
        ContainerInterface $container,
        CartService $cartService,
        OrderRepository $orderRepository,
        Vt4gPluginRepository $vt4gPluginRepository
    )
    {
        $this->container = $container;
        $this->cartService = $cartService;
        $this->orderRepository = $orderRepository;
        $this->vt4gPluginRepository = $vt4gPluginRepository;
        $this->em = $container->get('doctrine.orm.entity_manager');
        $this->vt4gConst = $container->getParameter('vt4g_plugin.const');
    }

    /**
     * フォーム生成
     *
     * @param  FormBuilderInterface $builder フォームビルダー
     * @param  array                $options フォーム生成に使用するデータ
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $preOrderId = $this->cartService->getPreOrderId();
        $order = $this->orderRepository->findOneBy(['pre_order_id' => $preOrderId]);

        if (is_null($order)) {
            return;
        }

        $util = $this->container->get('vt4g_plugin.service.util');
        $payment = $order->getPayment();
        if (empty($payment)) {
            return;
        }

        // プラグイン設定
        $pluginSetting = $util->getPluginSetting();
        if (is_null($pluginSetting)) {
            return;
        }

        $withCapture = [];
        $withCapture['与信売上(与信と同時に売上処理も行います)'] = 1;
        $withCapture['与信のみ(与信成功後に売上処理を行う必要があります)'] = 0;

        $suppressShippingAddressView = [];
        $suppressShippingAddressView["配送先表示"] = 0;
        $suppressShippingAddressView["配送先表示抑止"] = 1;


        $builder
            ->add('noteToBuyer', TextType::class, [
                'label' => '注文説明',
            ])
            ->add('withCapture', ChoiceType::class, [
                'label' => '売上フラグ',
                'choices' => $withCapture,
            ])
            ->add('suppressShippingAddressView', ChoiceType::class, [
                'label' => '配送先表示抑止フラグ',
                'choices' => $suppressShippingAddressView,
            ]);

    }
}
