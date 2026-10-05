<?php

/**
 * 并发校验的参考实现：直接复用促销扩展的源码
 * （examples/promo-ext/src/promo.php），不再在商城侧另写一份规则。
 *
 * shop_fpm 里的 promo_quote() 是 AOT 编译产物，这里用解释器跑同一份源码，
 * 逐字段比对可以同时验证：AOT 结果与 PHP 语义一致、请求之间没有状态串扰。
 */

require __DIR__ . '/../../promo-ext/src/promo.php';

/**
 * 生成确定性的、互不相同的测试用例。
 *
 * @return array{0: array, 1: array}
 */
function reference_case(int $seed): array
{
    mt_srand($seed);

    $levels = ['guest', 'silver', 'gold'];
    $coupons = [null, 'SAVE20', 'OFF10', 'FAKE'];

    $cart = [];
    $lines = mt_rand(1, 3);
    for ($i = 0; $i < $lines; $i++) {
        $cart[] = [
            'price' => mt_rand(10, 400) + mt_rand(0, 99) / 100,
            'qty' => mt_rand(1, 3),
            'tag' => mt_rand(0, 4) === 0 ? 'digital' : 'physical',
        ];
    }

    $context = [
        'level' => $levels[mt_rand(0, count($levels) - 1)],
        'coupon' => $coupons[mt_rand(0, count($coupons) - 1)],
        'shipping' => (float)mt_rand(0, 15),
    ];

    return [$cart, $context];
}
