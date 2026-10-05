<?php
function stringMultiply(string $value, int $factor): mixed
{
    $result = $value * $factor;
    return $result;
}
function literalDivide(): mixed
{
    $result = "1e2" / 3;
    return $result;
}
function nativeBoolSum(bool $left, bool $right): int
{
    $result = $left + $right;
    return $result;
}
function nativeCompare(bool $left, float $right): bool
{
    return $left == $right;
}
