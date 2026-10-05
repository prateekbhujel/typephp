--TEST--
Numeric strings retain Zend integer boundaries, overflow, division and signed zero
--FILE--
<?php
function main(): void {
    var_dump("09223372036854775807" + 0);
    var_dump("  +9223372036854775807\t" + 0);
    var_dump("-9223372036854775808" + 0);
    var_dump("-9223372036854775808" % 3);
    var_dump("-9223372036854775808" / 2);
    var_dump("9223372036854775807" * 2);
    var_dump("9223372036854775808" + 0);
    var_dump("5" / 2);
    $s = "5";
    $divisor = 2;
    $result = $s / $divisor;
    var_dump($result);
    var_dump("1e-400" * 2);
    var_dump("-0.0" * 1);
    var_dump("1e400" * 2);
}
?>
--EXPECT--
int(9223372036854775807)
int(9223372036854775807)
int(-9223372036854775808)
int(-2)
int(-4611686018427387904)
float(1.8446744073709552E+19)
float(9.223372036854776E+18)
float(2.5)
float(2.5)
float(0)
float(-0)
float(INF)
