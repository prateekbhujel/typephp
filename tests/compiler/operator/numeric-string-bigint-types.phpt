--TEST--
bigint_types parses original string operands without losing integer precision
--FILE--
<?php
use bigint_types;

function main(): void {
    $literal = "9223372036854775807" * 2;
    $source = "9223372036854775807";
    $variable = $source * 2;
    echo $literal->toString(), "\n";
    echo $variable->toString(), "\n";
    $quotient = "10" / 2;
    echo $quotient->toString(), "\n";
}
?>
--EXPECT--
18446744073709551614
18446744073709551614
5
