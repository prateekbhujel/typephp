<?php

/**
 * 商品种子数据，AOT 编译进 shop_fpm 二进制。
 * 首次访问时由 lib/bootstrap.php 写入 SQLite。
 */
function shop_catalog(): array
{
    return [
        [
            'sku' => 'BK-1001',
            'name' => 'TypePHP 实战手册',
            'price' => 129.00,
            'digital' => 0,
            'stock' => 42,
            'desc' => '从扩展编译到 php-fpm 部署',
        ],
        [
            'sku' => 'BK-1002',
            'name' => 'PHP 内核剖析',
            'price' => 189.00,
            'digital' => 0,
            'stock' => 18,
            'desc' => 'Zend VM 与扩展开发',
        ],
        [
            'sku' => 'KB-2001',
            'name' => '机械键盘 87 键',
            'price' => 399.00,
            'digital' => 0,
            'stock' => 7,
            'desc' => '茶轴 / 蓝牙双模',
        ],
        [
            'sku' => 'MS-3001',
            'name' => '人体工学鼠标',
            'price' => 159.00,
            'digital' => 0,
            'stock' => 25,
            'desc' => '垂直握持，缓解手腕压力',
        ],
        [
            'sku' => 'AC-4001',
            'name' => 'USB-C 数据线 1m',
            'price' => 19.00,
            'digital' => 0,
            'stock' => 120,
            'desc' => '低价实物，用来演示运费规则',
        ],
        [
            'sku' => 'DL-9001',
            'name' => 'TypePHP 视频课程',
            'price' => 99.00,
            'digital' => 1,
            'stock' => 9999,
            'desc' => '可下载，不计运费',
        ],
        [
            'sku' => 'DL-9002',
            'name' => 'Swoole 进阶电子书',
            'price' => 59.00,
            'digital' => 1,
            'stock' => 9999,
            'desc' => 'PDF，可下载，不计运费',
        ],
    ];
}
