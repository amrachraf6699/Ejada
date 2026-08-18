<?php

namespace Tests\Feature;

use App\Models\CertificateTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateTemplateCloneTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_can_be_cloned_with_a_new_name_and_background_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('certificate-templates/original.png', 'image contents');
        $template = CertificateTemplate::create([
            'name' => 'Original certificate',
            'background_path' => 'certificate-templates/original.png',
            'width' => 1200,
            'height' => 800,
            'canvas_json' => ['objects' => []],
            'is_active' => true,
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->post(route('certificate-templates.clone', $template), ['name' => 'Copied certificate']);

        $clone = CertificateTemplate::where('name', 'Copied certificate')->firstOrFail();

        $response->assertRedirect(route('certificate-templates.edit', $clone));
        $this->assertNotSame($template->background_path, $clone->background_path);
        Storage::disk('public')->assertExists($clone->background_path);
        $this->assertSame($template->canvas_json, $clone->canvas_json);
    }
}
