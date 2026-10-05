<?php

/**
 * JSON 接口：给定购物车与优惠上下文，返回 shop_pricing() 的完整计算结果。
 *
 * 页面走的是 HTML，这个接口是为了让 bench/concurrency.php 能在并发下逐字段校验价格计算，
 * 也可以当作商城前端的「试算」接口使用。
 *
 * 参数：id、cart（JSON 数组）、level、coupon、ship
 */

require __DIR__ . '/../lib/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$id = (string)($_GET['id'] ?? '');
$cart = json_decode((string)($_GET['cart'] ?? ''), true);

if (!is_array($cart)) {
    http_response_code(400);
    echo json_encode(['id' => $id, 'error' => 'bad_cart']);
    return;
}

$lines = [];
foreach ($cart as $item) {
    $lines[] = [
        'sku' => (string)($item['sku'] ?? ''),
        'name' => (string)($item['name'] ?? ''),
        'price' => (float)($item['price'] ?? 0.0),
        'qty' => (int)($item['qty'] ?? 0),
        'digital' => (($item['tag'] ?? '') === 'digital' || !empty($item['digital'])) ? 1 : 0,
    ];
}

$coupon = (string)($_GET['coupon'] ?? '');

echo json_encode([
    'id' => $id,
    'pid' => getmypid(),
    'seq' => shop_probe_seq(),
    'quote' => shop_pricing($lines, [
        'level' => (string)($_GET['level'] ?? 'guest'),
        'coupon' => $coupon === '' ? null : $coupon,
        'shipping' => (float)($_GET['ship'] ?? 0.0),
    ]),
]);
