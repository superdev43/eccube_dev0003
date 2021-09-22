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
class PaymentCreditType extends AbstractType
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

        // 有効期限(月) 1~12月
        $expiryMonthList = array_map(function ($month) use ($util) {
            return $util->zeroPadding($month);
        }, range(1, 12));
        $expiryMonthChoices = array_combine($expiryMonthList, $expiryMonthList);

        // 有効期限(年) 本年~10年後まで
        $currentYear = date('y');
        $expiryYearList = range($currentYear, $currentYear + 10);

        // ダミーモードで動作時にデバッグ用の選択肢を追加
        if ($pluginSetting['dummy_mode_flg'] == 1) {
            $expiryYearList[] = $this->vt4gConst['VT4G_CREDIT_ERR_RESPONSE_YEAR98'];
            $expiryYearList[] = $this->vt4gConst['VT4G_CREDIT_ERR_RESPONSE_YEAR99'];
        }
        $expiryYearChoices = array_combine($expiryYearList, $expiryYearList);

        $payMethodMap = $this->vt4gConst['VT4G_FORM']['CHOICES']['CREDIT_PAY_METHOD'];
        $payMethodList = (array)$options['data']['paymentInfo']['credit_pay_methods'];
        $payMethodChoices = [];
        // クレジットカード決済の設定で有効な支払い種別を絞り込む
        foreach ($payMethodMap as $label => $value) {
            if (in_array($value, $payMethodList, true)) {
                $payMethodChoices[$label] = $value;
            }
        }

        $cardRegistChoices = $this->vt4gConst['VT4G_FORM']['CHOICES']['REGISTRATION_FLG'];
        unset($cardRegistChoices['初期値なし']);
        $cardRegistDefault = $options['data']['paymentInfo']['cardinfo_regist_default'] ?? $this->vt4gConst['VT4G_FORM']['DEFAULT']['CARDINFO_REGIST_DEFAULT'];

        $builder
            ->add('card_no', TextType::class, [
                'label' => 'カード番号',
                'attr' => [
                    'minlength' => $this->vt4gConst['VT4G_FORM']['LENGTH']['MIN']['CARD_NO'],
                    'maxlength' => $this->vt4gConst['VT4G_FORM']['LENGTH']['MAX']['CARD_NO']
                ],
            ])
            ->add('security_code', TextType::class, [
                'label' => 'セキュリティコード',
                'attr' => [
                    'minlength' => $this->vt4gConst['VT4G_FORM']['LENGTH']['MIN']['SECURITY_CODE'],
                    'maxlength' => $this->vt4gConst['VT4G_FORM']['LENGTH']['MAX']['SECURITY_CODE']
                ],
                'required' => true,
            ])
            ->add('last_name', TextType::class, [
                'label' => '姓（英字）',
                'attr' => [
                    'maxlength' => $this->vt4gConst['VT4G_FORM']['LENGTH']['MAX']['LAST_NAME'],
                    'placeholder' => '姓'
                ],
            ])
            ->add('first_name', TextType::class, [
                'label' => '名（英字）',
                'attr' => [
                    'maxlength' => $this->vt4gConst['VT4G_FORM']['LENGTH']['MAX']['FIRST_NAME'],
                    'placeholder' => '名'
                ],
            ])
            ->add('expiry_month', ChoiceType::class, [
                'label' => '有効期限(月)',
                'placeholder' => '--',
                'choices' => $expiryMonthChoices,
            ])
            ->add('expiry_year', ChoiceType::class, [
                'label' => '有効期限(年)',
                'placeholder' => '--',
                'choices' => $expiryYearChoices,
            ])
            ->add('payment_type', ChoiceType::class, [
                'label' => 'お支払い方法',
                'choices' => $payMethodChoices,
            ])
            ->add('cardinfo_regist', ChoiceType::class, [
                'label' => 'カード情報登録',
                'expanded' => true,
                'choices' => $cardRegistChoices,
                'data' => $cardRegistDefault,
            ]);

        if (!$options['data']['paymentInfo']['security_flg'] ?? false) {
            $builder->remove('security_code');
        }
    }
}
