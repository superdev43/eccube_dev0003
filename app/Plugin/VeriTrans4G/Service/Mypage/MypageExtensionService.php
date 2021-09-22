<?php
/*
 * Copyright (c) 2018 VeriTrans Inc., a Digital Garage company. All rights reserved.
 * http://www.veritrans.co.jp/
 */
namespace Plugin\VeriTrans4G\Service\Mypage;

use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * マイページ 拡張用クラス
 */
class MypageExtensionService
{
    /**
     * コンテナ
     */
    private $container;

    /**
     * コンストラクタ
     *
     * @param  ContainerInterface $container
     * @return void
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * マイページ レンダリング時のイベントリスナ
     * べリトランス会員IDが登録されていれば、マイページナビにメニューを追加します。
     * @param  TemplateEvent $event イベントデータ
     * @return void
     */
    public function onRenderBefore(TemplateEvent $event)
    {
        $token = $this->container->get('security.token_storage')->getToken();
        if (null === $token || empty($token->getUser()->vt4g_account_id)) {
            return;
        } else {
            $event->addSnippet('@VeriTrans4G/default/Mypage/navi.twig');
        }
    }

    /**
     * マイページ 注文履歴一覧画面情報取得時のイベントリスナ
     * 注文日時の登録が無いデータを対象外にします。
     * @param  EventArgs $event イベントデータ
     * @return void
     */
    public function onMypageIndexSearch(EventArgs $event)
    {
        $qb = $event->getArgument('qb');
        $qb->andWhere($qb->expr()->isNotNull('o.order_date'));
    }

}
