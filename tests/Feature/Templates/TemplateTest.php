<?php

namespace Tests\Feature\Templates;

use App\Models\RequestTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestData;

class TemplateTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    public function test_authenticated_user_can_list_templates(): void
    {
        ['token' => $token] = $this->createUserWithToken();
        RequestTemplate::factory()->count(3)->create();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/templates');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'type', 'description', 'is_active'],
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_list_templates(): void
    {
        $response = $this->getJson('/api/v1/templates');

        $response->assertUnauthorized();
    }

    public function test_template_list_only_shows_active_templates(): void
    {
        ['token' => $token] = $this->createUserWithToken();
        RequestTemplate::factory()->count(2)->create(['is_active' => true]);
        RequestTemplate::factory()->create(['is_active' => false]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/templates');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_authenticated_user_can_view_single_template(): void
    {
        ['token' => $token] = $this->createUserWithToken();
        $template = RequestTemplate::factory()->create();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson("/api/v1/templates/{$template->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'type',
                    'description',
                    'schema',
                    'default_approval_line',
                    'is_active',
                ],
            ])
            ->assertJsonPath('data.id', $template->id);
    }

    public function test_viewing_nonexistent_template_returns_404(): void
    {
        ['token' => $token] = $this->createUserWithToken();

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson('/api/v1/templates/999');

        $response->assertNotFound();
    }

    public function test_template_includes_schema_fields(): void
    {
        ['token' => $token] = $this->createUserWithToken();
        $template = RequestTemplate::factory()->create([
            'schema' => [
                'fields' => [
                    ['name' => 'reason', 'type' => 'text', 'label' => '사유'],
                ],
            ],
        ]);

        $response = $this->withHeaders($this->authHeaders($token))
            ->getJson("/api/v1/templates/{$template->id}");

        $response->assertOk()
            ->assertJsonPath('data.schema.fields.0.name', 'reason');
    }
}
