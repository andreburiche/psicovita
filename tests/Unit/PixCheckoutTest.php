<?php

namespace Tests\Unit;

use App\Support\PixCheckout;
use PHPUnit\Framework\TestCase;

class PixCheckoutTest extends TestCase
{
    public function test_static_fallback_image_without_payload_is_displayable(): void
    {
        $pix = [
            'image_url' => 'http://localhost/images/pix-bank.png',
            'raw' => ['static_fallback' => true],
        ];

        $this->assertTrue(PixCheckout::isDisplayable($pix));
    }

    public function test_encoded_image_with_payload_is_displayable(): void
    {
        $pix = [
            'encoded_image' => base64_encode('img'),
            'payload' => '00020126PIX',
        ];

        $this->assertTrue(PixCheckout::isDisplayable($pix));
    }

    public function test_image_without_payload_or_static_flag_is_not_displayable(): void
    {
        $pix = [
            'encoded_image' => base64_encode('img'),
        ];

        $this->assertFalse(PixCheckout::isDisplayable($pix));
    }
}
