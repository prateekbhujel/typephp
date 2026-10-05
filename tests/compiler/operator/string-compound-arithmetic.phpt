--TEST--
Compound arithmetic uses Zend before converting the result to fixed local storage
--FILE--
<?php
class NumericSlot {
    public float $value = 2.0;
    public int $integer = 2;
    public static float $shared = 2.0;
}
#[Native]
class NativeNumericSlot {
    public float $value = 2.0;
}
function main(): void {
    $i = 2;
    $i *= "1.5";
    var_dump($i);
    $f = 2.0;
    $f += "1.5";
    var_dump($f);
    $s = "1.5";
    $s *= 2;
    var_dump($s);
    $v = std::any("1.5");
    $v *= 2;
    var_dump($v);
    $n = 2;
    $n *= std::any("1.5");
    var_dump($n);
    $slot = new NumericSlot();
    $slot->value += "1.5";
    var_dump($slot->value);
    try {
        $slot->integer *= "1.5";
    } catch (TypeError $e) {
        echo "TypeError\n";
    }
    var_dump($slot->integer);
    NumericSlot::$shared += "1.5";
    var_dump(NumericSlot::$shared);
    $nativeSlot = new NativeNumericSlot();
    $nativeSlot->value += "1.5";
    var_dump($nativeSlot->value);
    try {
        $i /= "0";
    } catch (DivisionByZeroError $e) {
        echo "DivisionByZeroError\n";
    }
    var_dump($i);
}
?>
--EXPECT--
int(3)
float(3.5)
string(1) "3"
float(3)
int(3)
float(3.5)
TypeError
int(2)
float(3.5)
float(3.5)
DivisionByZeroError
int(3)
