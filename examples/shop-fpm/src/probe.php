<?php

/**
 * 并发探针，AOT 编译进 shop_fpm。
 *
 * 函数内的静态变量在标准 PHP 里属于「请求级」状态：php-fpm 的 worker 进程会长期存活，
 * 但每个请求都应从 0 重新开始计数。若这里出现累加，就说明状态跨请求泄漏了。
 * 接口 public/quote.php 把它放进响应里，由 bench/concurrency.php 校验。
 */
function shop_probe_seq(): int
{
    static $seq = 0;
    $seq++;

    return $seq;
}
