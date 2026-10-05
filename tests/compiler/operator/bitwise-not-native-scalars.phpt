--TEST--
Native bool, int and float bitwise not uses C++ integer arithmetic
--FILE--
<?php
function invertBool(bool $value): int { return ~$value; }
function invertInt(int $value): int { return ~$value; }
function invertFloat(float $value): int { return ~$value; }
function main(): void {
    var_dump(~false, ~true, invertBool(false), invertBool(true));
    var_dump(~5, invertInt(5), ~PHP_INT_MIN);
    var_dump(~1.5, invertFloat(1.5));
    $s = "abc";
    var_dump(~std::int($s));
}
?>
--EXPECT--
int(-1)
int(-2)
int(-1)
int(-2)
int(-6)
int(-6)
int(9223372036854775807)
int(-2)
int(-2)
int(-1)
