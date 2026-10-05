--TEST--
varint_types does not narrow string arithmetic results to native scalar types
--FILE--
<?php
use varint_types;

function main(): void {
    $s = "1.5";
    $result = $s * 2;
    var_dump($result, "1.5" * 2, "1.5" * 2.0);
    $scientific = "1e2";
    $division = $scientific / 3;
    var_dump($division, "1e2" / 3);
    var_dump("1.0" & 1, "2e0" << 1, "-9223372036854775808" % 3);
    $v = 2;
    $v *= "1.5";
    var_dump($v);
    $native = std::int(2);
    $native *= "1.5";
    var_dump($native);
    $f = 2.0;
    $f += "1.5";
    var_dump($f);
}
?>
--EXPECT--
float(3)
float(3)
float(3)
float(33.333333333333336)
float(33.333333333333336)
int(1)
int(4)
int(-2)
float(3)
int(3)
float(3.5)
