<?php

use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    public function test_isValidNickname() {
        $helper = new \App\Models\Helper;
        $isValid = $helper->isValidNickname('Testßäüö');
        $this->assertTrue($isValid);
        $isValid = $helper->isValidNickname('"1=1;');
        $this->assertFalse($isValid);
        $isValid = $helper->isValidNickname('1test');
        $this->assertFalse($isValid);
        $isValid = $helper->isValidNickname('hallo-test');
        $this->assertFalse($isValid);
    }
}