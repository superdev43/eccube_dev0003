<?php
/*
 * Copyright (c) 2018 VeriTrans Inc., a Digital Garage company. All rights reserved.
 * http://www.veritrans.co.jp/
 */
namespace Plugin\VeriTrans4G;

use Eccube\Plugin\AbstractPluginManager;
use Eccube\Entity\Csv;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Entity\Payment;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Plugin\VeriTrans4G\Entity\Vt4gPlugin;
use Plugin\VeriTrans4G\Service\Admin\PluginConfigService;
use Plugin\VeriTrans4G\Service\Vt4gMdkService;


/**
 * ベリトランス4G用プラグインマネージャー
 */
class PluginManager extends AbstractPluginManager
{

    /**
     * コピー元ディレクトリ
     * @var string
     */
    private $imgSrc;

    /**
     * コピー先ディレクトリ
     * @var string
     */
    private $imgDst;

    /**
     * MDK Logger
     * @var \TGMDK_Logger
     */
    private $mdkLogger;


    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->imgSrc = __DIR__ . '/Resource/copy/';
        $this->imgDst = __DIR__  . '/../../../html/plugin/vt4g';
    }

    /**
     * プラグインインストール時の処理
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function install(array $meta, ContainerInterface $container)
    {
        $mdkService = new Vt4gMdkService($container);
        $mdkService->checkMdk(false,false);
        $this->mdkLogger = $mdkService->getMdkLogger();

        $this->mdkLogger->info('プラグインのインストールを開始します。');
        $this->copyFiles($this->imgSrc,$this->imgDst);
        $this->insertPlgVt4GPlugin($container);
        $this->mdkLogger->info('プラグインのインストールが完了しました。');
    }

    /**
     * プラグインアップデート時の処理
     *
     * @param array              $meta
     * @param ContainerInterface $container
     */
    public function update(array $meta, ContainerInterface $container)
    {
        $mdkService = new Vt4gMdkService($container);
        $mdkService->checkMdk(true,true);
        $this->mdkLogger = $mdkService->getMdkLogger();

        $this->mdkLogger->info('プラグインのアップデートを開始します。');
        $this->registerPageForUpdate($container);
        $this->registerCsvForUpdate($container);
        $this->copyFiles($this->imgSrc,$this->imgDst);
        $this->removeFiles(__DIR__ . '/Form/Type/Admin/OrderEditType.php');
        $this->removeFiles($container->getParameter('plugin_realdir') . '/VeriTrans4G/Resource/tgMdkPHP/tgMdk/3GPSMDK.properties');
        $this->removeFiles($container->getParameter('plugin_realdir') . '/VeriTrans4G/Resource/tgMdkPHP/tgMdk/log4php.properties');
        $this->mdkLogger->info('プラグインのアップデートが完了しました。');
    }

    /**
     * プラグイン有効化時の処理
     *
     * @param  array              $meta
     * @param  ContainerInterface $container
     * @return void
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        $mdkService = new Vt4gMdkService($container);
        $mdkService->checkMdk();
        $this->mdkLogger = $mdkService->getMdkLogger();

        $this->mdkLogger->info('プラグインの有効化を開始します。');
        $this->visiblePayment($container);
        $this->mdkLogger->info('プラグインの有効化が完了しました。');
    }

    /**
     * プラグイン無効化時の処理
     *
     * @param  array              $meta
     * @param  ContainerInterface $container
     * @return void
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        $mdkService = new Vt4gMdkService($container);
        $mdkService->checkMdk();
        $this->mdkLogger = $mdkService->getMdkLogger();

        $this->mdkLogger->info('プラグインの無効化を開始します。');
        $this->unvisiblePayment($container, false);
        $this->mdkLogger->info('プラグインの無効化が完了しました。');
    }

    /**
     * プラグインアンインストール時の処理
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        $mdkService = new Vt4gMdkService($container);
        $mdkService->checkMdk();
        $this->mdkLogger = $mdkService->getMdkLogger();

        $this->mdkLogger->info('プラグインのアンインストールを開始します。');
        $this->removeFiles($this->imgDst);
        $this->removeFiles($container->getParameter('eccube_theme_app_dir').'/default/VeriTrans4G');
        $this->unvisiblePayment($container);
        $this->removePage($container);
        $this->removeCsv($container);
        $this->removeFiles($container->getParameter('plugin_data_realdir') . '/VeriTrans4G');
        $this->mdkLogger->info('プラグインのアンインストールが完了しました。');

    }

    /**
     * 第一引数に指定されたファイルを第二引数のパスにコピーします。
     * ディレクトリを指定した場合は、そのディレクトリ直下の階層をそのままコピーします。
     * @param string $src コピー元のパス
     * @param string $dst コピー先のパス
     */
    private function copyFiles($src, $dst)
    {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->copyFiles($src . '/' . $file, $dst . '/' . $file);
                } else {
                    $ret = copy($src . '/' . $file, $dst . '/' . $file);
                    if($ret == true) {
                        $this->mdkLogger->info($dst . '/' . $file.' にファイルを配置しました。');
                    }else{
                        $this->mdkLogger->info($dst . '/' . $file.' のコピーに失敗しました。');
                    }
                }
            }
        }
        closedir($dir);
    }

    /**
     * 指定されたファイルを削除します。<br/>
     * ディレクトリを指定した場合は、そのディレクトリごと削除します。
     *
     * @param  string  $dir 削除対象のパス
     */
    private function removeFiles($dir)
    {
        $fileSystem = new Filesystem();

        if (!$fileSystem->exists($dir)) {
            $this->mdkLogger->info(sprintf(' %s は存在しないため削除処理を行いません。',$dir));
            return;
        }

        $fileSystem->remove($dir);
        $this->mdkLogger->info(sprintf('%s を削除しました。',$dir));
    }

    /**
     * plg_vbt4g_pluginにレコードを追加します。
     * @param ContainerInterface $container
     */
    private function insertPlgVt4GPlugin($container)
    {
        $Vt4gPlugin = new Vt4gPlugin();
        $Vt4gPlugin->setPluginCode('VeriTrans4G');
        $Vt4gPlugin->setPluginName('ベリトランス4G決済');
        $Vt4gPlugin->setAutoUpdateFlg('0');
        $Vt4gPlugin->setDelFlg('0');
        $Vt4gPlugin->setCreateDate(date('Y-m-d H:i:s'));
        $Vt4gPlugin->setUpdateDate(date('Y-m-d H:i:s'));

        $em = $container->get('doctrine.orm.entity_manager');
        $em->persist($Vt4gPlugin);
        $em->flush($Vt4gPlugin);

        $this->mdkLogger->info('plg_vt4g_pluginに初期データを登録しました。');
    }

    /**
     * dtb_paymentにあるベリトランス支払方法を表示にします。
     * @param  ContainerInterface $container    コンテナ
     * @return void
     */
    private function visiblePayment($container)
    {
        $util              = $container->get('vt4g_plugin.service.util');
        $em                = $container->get('doctrine.orm.entity_manager');
        $paymentRepository = $em->getRepository(Payment::class);

        $vt4gPaymentMethodList = $util->getVt4gPaymentMethodList();

        if (empty($vt4gPaymentMethodList)) {
            return;
        }

        foreach ((array)$vt4gPaymentMethodList as $vt4gPaymentMethod) {
            $paymentId = $vt4gPaymentMethod['id'];
            $payment = $paymentRepository->find($paymentId);
            $payment->setVisible(true);
            $em->flush();
            $this->mdkLogger->info(sprintf('dtb_paymentの支払方法ID：%s を表示にしました。', $paymentId));
        }
    }

    /**
     * dtb_paymentにあるベリトランス支払方法を非表示にします。
     * @param  ContainerInterface $container    コンテナ
     * @param  boolean            $forUninstall アンインストール用処理フラグ
     * @return void
     */
    private function unvisiblePayment($container, $forUninstall = true)
    {
        $util              = $container->get('vt4g_plugin.service.util');
        $em                = $container->get('doctrine.orm.entity_manager');
        $paymentRepository = $em->getRepository(Payment::class);

        $vt4gPaymentMethodList = $util->getVt4gPaymentMethodList();

        if (empty($vt4gPaymentMethodList)) {
            return;
        }
        foreach ((array)$vt4gPaymentMethodList as $vt4gPaymentMethod) {
            $payment = $paymentRepository->find($vt4gPaymentMethod['id']);
            if ($forUninstall) {
                $name = $payment->getMethod();
                $payment->setMethod($name.'(VT4Gプラグイン削除済み)');
            }
            $payment->setVisible(false);
            $em->persist($payment);
            $em->flush();
            $this->mdkLogger->info(sprintf('dtb_paymentの支払方法ID：%s を非表示にしました。', $vt4gPaymentMethod['id']));
        }
    }

    /**
     * アップデートで追加となったページ・ページレイアウトを登録します。
     * @param ContainerInterface $container コンテナ
     */
    private function registerPageForUpdate($container)
    {
        $config = new PluginConfigService($container);
        $urlConsts = array(
            [
                'INDEX' => [
                    'NAME'     => 'mypage_vt4g_account_id',
                    'LABEL'    => 'MYページ/ベリトランス会員ID',
                    'TEMPLATE' => 'VeriTrans4G/Resource/template/default/Mypage/vt4g_account_id',
                ]
            ],
            [
                'INDEX' => [
                    'NAME'     => 'vt4g_admin_preview_rakuten_button',
                    'LABEL'    => '商品購入/楽天ペイ支払いボタン',
                    'TEMPLATE' => 'VeriTrans4G/Resource/template/default/Shopping/vt4g_button_rakuten',
                ]
            ]
        );
        foreach($urlConsts as $urlConst) {
            $config->registerPageLayout($urlConst,true);
            $this->mdkLogger->info(sprintf('dtb_pageとdtb_page_layoutに%sのページ情報を登録しました。', $urlConst['INDEX']['LABEL']));
        }
    }

    /**
     * ページ・ページレイアウトを削除します。
     * @param ContainerInterface $container コンテナ
     */
    private function removePage($container)
    {
        $em   = $container->get('doctrine.orm.entity_manager');
        $urls = [
            'vt4g_shopping_payment',
            'mypage_vt4g_account_id',
            'vt4g_admin_preview_rakuten_button',
        ];

        foreach ($urls as $url) {
            $page = $em->getRepository(Page::class)->findOneBy(compact('url'));
            if (is_null($page)) {
                continue;
            }

            $pageLayout = $em->getRepository(PageLayout::class)->findOneBy(['page_id' => $page->getId()]);
            if (!is_null($pageLayout)) {
                $em->remove($pageLayout);
                $em->flush();
            }

            $em->remove($page);
            $em->flush();
            $this->mdkLogger->info(sprintf('dtb_pageとdtb_page_layoutからurl:%s を削除しました。', $url));
        }
    }

    /**
     * アップデートで追加となったCSV項目を登録します。
     * @param ContainerInterface $container コンテナ
     */
    private function registerCsvForUpdate($container)
    {
        $config = new PluginConfigService($container);
        $csvConst = [
            'VT4G_ACCOUNT_ID' => [
                'DISP_NAME'  => 'ベリトランス会員ID',
                'FIELD_NAME' => 'vt4g_account_id',
            ]
        ];

        $config->registerCustomerCsv($csvConst, true);
        $this->mdkLogger->info(sprintf('dtb_csvに%sの項目情報を登録しました。', $csvConst['VT4G_ACCOUNT_ID']['FIELD_NAME']));
    }

    /**
     * CSV項目を削除します。
     * @param ContainerInterface $container コンテナ
     */
    private function removeCsv($container)
    {
        $em  = $container->get('doctrine.orm.entity_manager');
        $csv = $em->getRepository(Csv::class)->findOneBy(["CsvType" => CsvType::CSV_TYPE_CUSTOMER,'field_name' => 'vt4g_account_id']);
        if (!is_null($csv)) {
            $em->remove($csv);
            $em->flush();
            $this->mdkLogger->info(sprintf('dtb_csvからfield_name:%s を削除しました。', 'vt4g_account_id'));
        }

    }

}
