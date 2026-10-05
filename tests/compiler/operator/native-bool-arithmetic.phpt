--TEST--
Native bool, int and float operands use C++ scalar arithmetic and comparisons
--FILE--
<?php
function sumBools(bool $left, bool $right): int {
    $result = $left + $right;
    return $result;
}
function main(): void {
    var_dump(true + true, sumBools(true, true), true * 3, true / 2);
    var_dump(false + 2.5, true * 2.5);
    var_dump(true == 2, true < 2, false == -1, false > -1);
    var_dump(1.5 % 2, 2.5 & 1, 2.5 << 1);
    var_dump(2 ** 3, 2.0 ** 3);
    $i = 2;
    $i *= 1.5;
    var_dump($i);
    $f = 2.5;
    $f %= 2;
    var_dump($f);
}
?>
--EXPECT--
int(2)
int(2)
int(3)
int(0)
float(2.5)
float(2.5)
bool(false)
bool(true)
bool(false)
bool(true)
int(1)
int(0)
int(4)
int(8)
float(8)
int(3)
float(0)
