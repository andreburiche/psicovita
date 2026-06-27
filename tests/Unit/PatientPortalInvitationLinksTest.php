<?php

namespace Tests\Unit;

use App\Support\PatientPortalInvitationLinks;
use Tests\TestCase;

class PatientPortalInvitationLinksTest extends TestCase
{
    public function test_whatsapp_body_puts_url_on_its_own_line(): void
    {
        $url = 'https://app.example.com/portal/activar/abc123token';
        $body = PatientPortalInvitationLinks::whatsAppBody(
            'Dr. Ana',
            'PsicoVita',
            $url,
            '30/06/2026',
        );

        $this->assertStringContainsString("\n\n{$url}\n\n", $body);
        $this->assertStringNotContainsString("{$url} (", $body);
        $this->assertStringContainsString('*PsicoVita*', $body);
    }

    public function test_whatsapp_body_warns_on_localhost_url(): void
    {
        $body = PatientPortalInvitationLinks::whatsAppBody(
            'Dr. Ana',
            'PsicoVita',
            'http://127.0.0.1:8080/portal/activar/token',
            '30/06/2026',
        );

        $this->assertStringContainsString('APP_PUBLIC_URL', $body);
    }
}
