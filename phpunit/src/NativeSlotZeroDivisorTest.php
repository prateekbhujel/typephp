<?php

/**
 * std::int()/std::float() opt into native C++ arithmetic, so a proven zero
 * divisor cannot be silently rerouted to PHP semantics; it keeps the
 * compile-time rejection used by native scalar storage.
 */
class NativeSlotZeroDivisorTest extends BaseTest
{
    public function testExplicitNativeSlotZeroDivisorIsRejected(): void
    {
        $this->exec('Cannot divide or modulo by zero', 'native_slot_zero_divisor.php');
    }

    public function testNativeBooleanZeroDivisorIsRejected(): void
    {
        $this->exec('Cannot divide or modulo by zero', 'native-bool-zero-divisor.php');
    }

    public function testNativeModuloRejectsFractionalDivisorThatConvertsToZero(): void
    {
        $this->exec('Cannot divide or modulo by zero', 'native-fractional-modulo-zero.php');
    }
}
