--TEST--
Bitwise not preserves string bytes, typed returns and nested expression results
--FILE--
<?php
function invertText(string $s): mixed { return ~$s; }
function invertLocalText(string $s): string {
    $result = ~$s;
    return $result;
}
function textOnce(int &$calls): string { ++$calls; return 'abc'; }
function main(): void {
    var_dump(bin2hex(~"abc"));
    var_dump(bin2hex(~""));
    var_dump(bin2hex(~"1e3"));
    $s = "abc";
    $result = ~$s;
    var_dump(is_string($result), bin2hex($result), $s);
    var_dump(bin2hex(invertText("abc")));
    var_dump(bin2hex(invertLocalText("ab")));
    var_dump(~~"xyz");
    var_dump(bin2hex(~"\x00\x7f\x80\xff"));
    $calls = 0;
    $once = ~textOnce($calls);
    var_dump(bin2hex($once), $calls);
}
?>
--EXPECT--
string(6) "9e9d9c"
string(0) ""
string(6) "ce9acc"
bool(true)
string(6) "9e9d9c"
string(3) "abc"
string(6) "9e9d9c"
string(4) "9e9d"
string(3) "xyz"
string(8) "ff807f00"
string(6) "9e9d9c"
int(1)
