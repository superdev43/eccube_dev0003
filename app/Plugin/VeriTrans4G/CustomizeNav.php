<?php
/*
 * Copyright (c) 2018 VeriTrans Inc., a Digital Garage company. All rights reserved.
 * http://www.veritrans.co.jp/
 */
namespace Plugin\VeriTrans4G;

use Eccube\Common\EccubeNav;

/**
 * 管理画面のメニューを追加
 */
class CustomizeNav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'order' => [
                'children' => [
                    'vt4g_order_csv_upload' => [
                        'name' => '受注CSVアップロード(決済更新)',
                        'url'  => 'vt4g_admin_order_csv_upload',
                    ],
                ],
            ],
            'setting' => [
                'children' => [
                    'system' => [
                        'children' => [
                            'vt4g_log_download' => [
                                'name' => 'ベリトランス4G ログダウンロード',
                                'url'  => 'vt4g_admin_log_download',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
