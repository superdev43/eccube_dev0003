<?php
/*
 * Copyright (c) 2018 VeriTrans Inc., a Digital Garage company. All rights reserved.
 * http://www.veritrans.co.jp/
 */
namespace Plugin\VeriTrans4G\Service\Shopping;

use Eccube\Event\TemplateEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Plugin\VeriTrans4G\Entity\Vt4gPaymentMethod;

/**
 * ご注文手続き画面 拡張用クラス
 */
class IndexExtensionService
{
    /**
     * コンテナ
     */
    protected $container;

    /**
     * エンティティーマネージャー
     */
    protected $em;

    /**
     * 汎用処理用サービス
     */
    protected $util;

    /**
     * VT用固定値配列
     */
    protected $vt4gConst;

    /**
     * コンストラクタ
     *
     * @param  ContainerInterface $container
     * @return void
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em        = $container->get('doctrine.orm.entity_manager');
        $this->util      = $container->get('vt4g_plugin.service.util');
        $this->vt4gConst = $container->getParameter('vt4g_plugin.const');
    }

    /**
     * ご注文手続き画面 レンダリング時のイベントリスナ
     * @param  TemplateEvent $event イベントデータ
     * @return void
     */
    public function onRenderBefore(TemplateEvent $event)
    {
        //plg_vt4g_payment_methodを取得
        $vt4gPaymentMethods = $this->em->getRepository(Vt4gPaymentMethod::class)->findAll();

        if (!empty($vt4gPaymentMethods)) {
            //plg_vt4g_pluginのサブデータから有効になっている決済内部IDを取得
            $enablePayIdList = $this->util->getEnablePayIdList();

            //有効な決済内部IDリストに存在しないplg_vt4g_payment_methodのpayment_idを対象外リストに追加
            $excludePaymentIdList = [];
            foreach ($vt4gPaymentMethods as $vt4gPaymentMethod) {
                if (!in_array($vt4gPaymentMethod->getMemo03(), $enablePayIdList)) {
                    $excludePaymentIdList[] = $vt4gPaymentMethod->getPaymentId();
                }
            }

            //お支払方法のFormViewから対象外リストにあるpayment_idを削除
            $form = $event->getParameter('form');
            $paymentFormViews = $form['Payment'];
            foreach ($paymentFormViews as $key => $paymentFormView){
                if (in_array($paymentFormView->vars['value'],$excludePaymentIdList)) {
                    $paymentFormViews->offsetUnset($key);
                }
            }

            $event->setParameter('form', $form);

        }
    }
}
