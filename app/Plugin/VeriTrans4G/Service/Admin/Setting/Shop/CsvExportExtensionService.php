<?php
/*
 * Copyright (c) 2018 VeriTrans Inc., a Digital Garage company. All rights reserved.
 * http://www.veritrans.co.jp/
 */

namespace Plugin\VeriTrans4G\Service\Admin\Setting\Shop;

use Eccube\Event\EventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * 管理管理CSV出力 拡張用クラス
 */
class CsvExportExtensionService
{
    /**
     * VT用固定値配列
     */
    private $vt4gConst;

    /**
     * コンストラクタ
     * @param ContainerInterface $container
     * @return void
     */
    public function __construct(ContainerInterface $container)
    {
        $this->vt4gConst = $container->getParameter('vt4g_plugin.const');
    }

    /**
     * 会員CSV出力の処理です。
     * @param EventArgs $event
     * @return void
     */
    public function onCustomerCsvExport(EventArgs $event)
    {
        if ($event->getArgument('Csv')->getFieldName() === $this->vt4gConst['VT4G_DTB_CSV']['CUSTOMER']['VT4G_ACCOUNT_ID']['FIELD_NAME']) {
            $event->getArgument('ExportCsvRow')->setData($event->getArgument('Customer')->vt4g_account_id);
        }
    }

}
