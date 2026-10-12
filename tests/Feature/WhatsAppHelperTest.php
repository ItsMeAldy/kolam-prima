<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_link_uses_site_setting_number(): void
    {
        SiteSetting::current()->update(['whatsapp_number' => '6281234567890']);

        $this->assertSame('https://wa.me/6281234567890', WhatsApp::link());
    }

    public function test_link_encodes_message(): void
    {
        SiteSetting::current()->update(['whatsapp_number' => '6281234567890']);

        $link = WhatsApp::link('Halo, saya tertarik dengan layanan Desain & Gambar.');

        $this->assertStringStartsWith('https://wa.me/6281234567890?text=', $link);
        $this->assertStringNotContainsString(' ', $link);
        $this->assertStringContainsString('Desain', $link);
    }

    public function test_link_strips_non_digits_from_number(): void
    {
        SiteSetting::current()->update(['whatsapp_number' => '+62 812-3456-7890']);

        $this->assertSame('https://wa.me/6281234567890', WhatsApp::link());
    }
}
