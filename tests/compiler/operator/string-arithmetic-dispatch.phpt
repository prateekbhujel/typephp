--TEST--
String literals and variables use Zend arithmetic without numeric literal rewriting
--FILE--
<?php
function multiplyString(string $value, int $factor): mixed {
    return $value * $factor;
}
function identityNumber(int $value): int {
    return $value;
}
function main(): void {
    $s = "1.5";
    $result = $s * 2;
    var_dump($result, $s * 2, "1.5" * 2, multiplyString($s, 2));
    $scientific = "1e2";
    $division = $scientific / 3;
    var_dump($division, "1e2" / 3, ("1.5" * 2) + identityNumber(1));
    var_dump("5" + 2, 2 + "5", "1.5" - 1, "2" ** -1);
    var_dump("1.5" % 2, "1.0" & 1, "2e0" << 1, "5" & "3");
    var_dump("-9223372036854775808" % 3);
    var_dump(false < "0.0", "0.0" && true);
    try {
        var_dump("not numeric" * 2);
    } catch (TypeError $e) {
        echo "TypeError\n";
    }
    try {
        var_dump("1.5" / 0);
    } catch (DivisionByZeroError $e) {
        echo "DivisionByZeroError\n";
    }
    try {
        var_dump(1 / "0");
    } catch (DivisionByZeroError $e) {
        echo "DivisionByZeroError\n";
    }
}
?>
--EXPECT--
float(3)
float(3)
float(3)
float(3)
float(33.333333333333336)
float(33.333333333333336)
float(4)
int(7)
int(7)
float(0.5)
float(0.5)
int(1)
int(1)
int(4)
string(1) "1"
int(-2)
bool(true)
bool(true)
TypeError
DivisionByZeroError
DivisionByZeroError
