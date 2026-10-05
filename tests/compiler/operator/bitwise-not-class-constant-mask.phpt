--TEST--
Bitwise not of class constants remains usable in local and typed property masks
--FILE--
<?php
class BitwiseFlags {
    public const FLAG = 1;
    public int $flags = 7;
    public function clear(): int {
        $this->flags &= ~ReflectionClassConstant::IS_PUBLIC;
        $this->flags &= ~self::FLAG;
        return $this->flags;
    }
}
#[Native]
class NativeBitwiseFlags {
    public int $flags = 7;
}
function main(): void {
    $flags = 7;
    $flags &= ~BitwiseFlags::FLAG;
    var_dump($flags);
    $box = new BitwiseFlags();
    var_dump($box->clear());
    $native = new NativeBitwiseFlags();
    $native->flags &= ~ReflectionClassConstant::IS_PUBLIC;
    var_dump($native->flags);
    $dynamic = std::any(7);
    $dynamic &= ~ReflectionClassConstant::IS_PUBLIC;
    var_dump($dynamic);
}
?>
--EXPECT--
int(6)
int(6)
int(6)
int(6)
