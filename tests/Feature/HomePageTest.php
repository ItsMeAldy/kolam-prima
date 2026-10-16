<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\Package;
use App\Models\Project;
use App\Models\Service;
use App\Models\SiteSetting;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SiteSetting::current();
    }

    public function test_landing_page_renders_successfully(): void
    {
        $this->get('/')->assertSuccessful()->assertSee('Kolam Prima', false);
    }

    public function test_landing_page_shows_services(): void
    {
        Service::create([
            'title' => 'Layanan Uji Coba',
            'description' => 'Deskripsi layanan uji coba.',
            'sort_order' => 1,
        ]);

        $this->get('/')->assertSuccessful()->assertSee('Layanan Uji Coba');
    }

    public function test_landing_page_shows_packages(): void
    {
        Package::create([
            'name' => 'Paket Uji Coba',
            'tagline' => 'Tagline paket.',
            'features' => ['Fitur A', 'Fitur B'],
            'is_popular' => true,
            'sort_order' => 1,
        ]);

        $this->get('/')->assertSuccessful()
            ->assertSee('Paket Uji Coba')
            ->assertSee('Fitur A');
    }

    public function test_landing_page_shows_only_published_projects(): void
    {
        $published = Project::create([
            'title' => 'Proyek Tampil',
            'slug' => 'proyek-tampil',
            'location' => 'Bandung',
            'property_type' => 'villa',
            'is_published' => true,
        ]);

        Project::create([
            'title' => 'Proyek Tersembunyi',
            'slug' => 'proyek-tersembunyi',
            'location' => 'Bogor',
            'property_type' => 'rumah',
            'is_published' => false,
        ]);

        $this->get('/')->assertSuccessful()
            ->assertSee('Proyek Tampil')
            ->assertDontSee('Proyek Tersembunyi');
    }

    public function test_landing_page_shows_published_videos_only(): void
    {
        Video::create([
            'title' => 'Video Tampil',
            'video_path' => 'media/videos/tampil.mp4',
            'is_published' => true,
        ]);

        Video::create([
            'title' => 'Video Tersembunyi',
            'video_path' => 'media/videos/tersembunyi.mp4',
            'is_published' => false,
        ]);

        $this->get('/')->assertSuccessful()
            ->assertSee('Video Tampil')
            ->assertDontSee('Video Tersembunyi');
    }

    public function test_landing_page_shows_uploaded_branding_images(): void
    {
        SiteSetting::query()->update([
            'hero_image' => 'media/hero/hero.jpg',
            'logo_path' => 'media/branding/logo.png',
        ]);

        $this->get('/')->assertSuccessful()
            ->assertSee('media/hero/hero.jpg', false)
            ->assertSee('media/branding/logo.png', false);
    }

    public function test_landing_page_shows_uploaded_service_image(): void
    {
        Service::create([
            'title' => 'Layanan Bergambar',
            'description' => 'Deskripsi.',
            'image_path' => 'media/services/ikon.png',
            'sort_order' => 1,
        ]);

        $this->get('/')->assertSuccessful()->assertSee('media/services/ikon.png', false);
    }

    public function test_landing_page_shows_published_faqs_only(): void
    {
        Faq::create([
            'question' => 'Pertanyaan Tampil?',
            'answer' => 'Jawaban tampil.',
            'is_published' => true,
        ]);

        Faq::create([
            'question' => 'Pertanyaan Tersembunyi?',
            'answer' => 'Jawaban tersembunyi.',
            'is_published' => false,
        ]);

        $this->get('/')->assertSuccessful()
            ->assertSee('Pertanyaan Tampil?')
            ->assertSee('Jawaban tampil.')
            ->assertDontSee('Pertanyaan Tersembunyi?');
    }

    public function test_landing_page_renders_faqpage_schema(): void
    {
        Faq::create([
            'question' => 'Berapa lama pengerjaan?',
            'answer' => 'Sekitar tiga minggu.',
            'sort_order' => 1,
        ]);

        $this->get('/')->assertSuccessful()
            ->assertSee('"@context": "https://schema.org"', false)
            ->assertSee('"@type": "FAQPage"', false)
            ->assertSee('"@type": "Question"', false)
            ->assertSee('Berapa lama pengerjaan?');
    }

    public function test_json_ld_schemas_are_valid_and_free_of_blade_leaks(): void
    {
        Faq::create([
            'question' => 'Berapa lama pengerjaan?',
            'answer' => 'Sekitar tiga minggu.',
            'sort_order' => 1,
        ]);

        $response = $this->get('/')->assertSuccessful();

        $response->assertSee('"@context": "https://schema.org"', false)
            ->assertDontSee('$__contextArgs', false);

        preg_match_all(
            '#<script type="application/ld\+json">(.*?)</script>#s',
            $response->getContent(),
            $matches,
        );

        $this->assertNotEmpty($matches[1], 'Tidak ada blok JSON-LD yang ditemukan.');

        foreach ($matches[1] as $json) {
            $decoded = json_decode(trim($json), true);

            $this->assertIsArray($decoded, 'Blok JSON-LD tidak dapat di-decode.');
            $this->assertSame('https://schema.org', $decoded['@context'] ?? null);
        }
    }

    public function test_landing_page_omits_faqpage_schema_when_no_faq_published(): void
    {
        Faq::create([
            'question' => 'Pertanyaan Draf?',
            'answer' => 'Belum tayang.',
            'is_published' => false,
        ]);

        $this->get('/')->assertSuccessful()
            ->assertDontSee('FAQPage', false);
    }

    public function test_landing_page_contains_whatsapp_links(): void
    {
        $this->get('/')->assertSuccessful()->assertSee('wa.me', false);
    }

    public function test_rendering_home_does_not_leak_output_buffer_when_hero_subheadline_null(): void
    {
        SiteSetting::query()->update([
            'hero_subheadline' => null,
            'tagline' => null,
        ]);

        $level = ob_get_level();

        $this->get('/')->assertSuccessful();

        $this->assertSame($level, ob_get_level(), 'Output buffer bocor saat render halaman utama.');
    }

    public function test_unknown_page_returns_custom_404(): void
    {
        $this->get('/halaman-tidak-ada')->assertNotFound()->assertSee('Halaman Tidak Ditemukan');
    }

    public function test_sitemap_and_robots_are_available(): void
    {
        $this->get('/sitemap.xml')->assertSuccessful()->assertSee('urlset', false);
        $this->get('/robots.txt')->assertSuccessful()->assertSee('Sitemap', false);
    }
}
