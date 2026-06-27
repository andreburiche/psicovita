<?php

namespace Tests\Unit;

use App\Support\ExternalContactUrls;
use Tests\TestCase;

class ExternalContactUrlsTest extends TestCase
{
    public function test_mailto_returns_null_for_invalid_email(): void
    {
        $this->assertNull(ExternalContactUrls::mailto(''));
        $this->assertNull(ExternalContactUrls::mailto('not-an-email'));
    }

    public function test_mailto_builds_url_with_subject(): void
    {
        $url = ExternalContactUrls::mailto('a@b.co', 'Olá', null);

        $this->assertStringStartsWith('mailto:a@b.co?', $url);
        $this->assertStringContainsString('subject=', $url);
        $this->assertStringContainsString(rawurlencode('Olá'), $url);
    }

    public function test_whatsapp_strips_non_digits(): void
    {
        $url = ExternalContactUrls::whatsapp('+351 912 345 678', 'Oi');

        $this->assertSame('https://wa.me/351912345678?text='.rawurlencode('Oi'), $url);
    }

    public function test_whatsapp_returns_null_for_empty_phone(): void
    {
        $this->assertNull(ExternalContactUrls::whatsapp(null));
        $this->assertNull(ExternalContactUrls::whatsapp('   -- '));
    }
}
