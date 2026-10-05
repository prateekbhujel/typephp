<?php

/**
 * 商城的报价适配层，AOT 编译进 shop_fpm 二进制。
 *
 * 折扣规则与金额计算复用促销扩展的源码（examples/promo-ext/src/promo.php，
 * 由 project.fpm.yml 通过 `sources: ../promo-ext/src` 一起编进 shop_fpm）。
 * 本文件不再自己写一份规则，只承担商城侧的两件事：
 *
 *   1. 等级别名：页面用 guest 表示无会员，促销引擎用 none，来回各转一次；
 *   2. 行投影：把购物车行补成带 sku/name/amount 的明细，并透传引擎算好的金额。
 *
 * 页面、会话、数据库都在请求脚本里，这里只做纯计算。
 */

/** 页面默认等级名，促销引擎里对应 none。 */
const SHOP_LEVEL_DEFAULT = 'guest';

/** 页面等级名 → 促销引擎等级名。其余等级两边同名，直接透传。 */
function shop_engine_level(string $level): string
{
    return $level === SHOP_LEVEL_DEFAULT ? 'none' : $level;
}

/** 促销引擎的等级表（none/silver/gold），键名换成页面用的 guest。 */
function shop_levels(): array
{
    $levels = [];
    foreach (promo_rules()['level_rate'] as $level => $rate) {
        $levels[$level === 'none' ? SHOP_LEVEL_DEFAULT : $level] = (float)$rate;
    }

    return $levels;
}

/** 优惠券表直接来自促销引擎，页面按 code => ['type','value','min'] 渲染。 */
function shop_coupons(): array
{
    return promo_rules()['coupons'];
}

function shop_shipping_fee(): float
{
    return 12.0;
}

/**
 * @param array $lines   [['sku' => string, 'name' => string, 'price' => float, 'qty' => int, 'digital' => bool], ...]
 * @param array $context ['level' => string, 'coupon' => string|null, 'shipping' => float]
 */
function shop_pricing(array $lines, array $context = []): array
{
    $items = [];
    $cart = [];
    foreach ($lines as $line) {
        $price = (float)$line['price'];
        $qty = (int)$line['qty'];
        $items[] = [
            'sku' => (string)$line['sku'],
            'name' => (string)$line['name'],
            'price' => $price,
            'qty' => $qty,
            'amount' => round($price * $qty, 2),
        ];
        // 促销引擎只关心 price/qty 与 digital 标签。
        $cart[] = [
            'price' => $price,
            'qty' => $qty,
            'tag' => empty($line['digital']) ? 'physical' : 'digital',
        ];
    }

    $level = (string)($context['level'] ?? SHOP_LEVEL_DEFAULT);
    $quote = promo_quote($cart, [
        'level' => shop_engine_level($level),
        'coupon' => $context['coupon'] ?? null,
        'shipping' => (float)($context['shipping'] ?? 0.0),
    ]);

    return [
        'items' => $items,
        'count' => count($items),
        'subtotal' => $quote['subtotal'],
        'level' => $level,
        'memberDiscount' => $quote['memberDiscount'],
        'thresholdDiscount' => $quote['thresholdDiscount'],
        'couponDiscount' => $quote['couponDiscount'],
        'coupon' => $quote['coupon'],
        'shipping' => $quote['shipping'],
        'total' => $quote['total'],
    ];
}
