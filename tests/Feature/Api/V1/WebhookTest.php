<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_requires_authentication_for_webhook_operations()
    {
        $response = $this->getJson('/api/v1/webhooks');
        $response->assertStatus(401);

        $response = $this->postJson('/api/v1/webhooks', [
            'url' => 'https://example.com/webhook',
            'events' => ['analysis.completed']
        ]);
        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_list_user_webhooks()
    {
        Sanctum::actingAs($this->user);

        // Create some webhooks for the user
        $webhook1 = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Webhook 1',
            'url' => 'https://example.com/webhook1',
            'events' => ['analysis.completed'],
            'active' => true
        ]);

        $webhook2 = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Webhook 2',
            'url' => 'https://example.com/webhook2',
            'events' => ['batch.completed'],
            'active' => false
        ]);

        // Create webhook for another user (should not appear)
        $otherUser = User::factory()->create();
        Webhook::factory()->create([
            'user_id' => $otherUser->id,
            'url' => 'https://example.com/other-webhook'
        ]);

        $response = $this->getJson('/api/v1/webhooks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items' => [
                        '*' => [
                            'id',
                            'name',
                            'url',
                            'events',
                            'active',
                            'secret_configured',
                            'created_at',
                            'updated_at',
                            'delivery_stats'
                        ]
                    ],
                    'pagination'
                ]
            ])
            ->assertJsonCount(2, 'data.items');
    }

    /** @test */
    public function it_can_filter_webhooks_by_active_status()
    {
        Sanctum::actingAs($this->user);

        Webhook::factory()->create([
            'user_id' => $this->user->id,
            'active' => true
        ]);

        Webhook::factory()->create([
            'user_id' => $this->user->id,
            'active' => false
        ]);

        // Filter for active webhooks
        $response = $this->getJson('/api/v1/webhooks?active=true');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items');

        // Filter for inactive webhooks
        $response = $this->getJson('/api/v1/webhooks?active=false');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items');
    }

    /** @test */
    public function it_validates_webhook_creation_data()
    {
        Sanctum::actingAs($this->user);

        // Missing required fields
        $response = $this->postJson('/api/v1/webhooks', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url', 'events']);

        // Invalid URL
        $response = $this->postJson('/api/v1/webhooks', [
            'url' => 'not-a-url',
            'events' => ['analysis.completed']
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);

        // Invalid events
        $response = $this->postJson('/api/v1/webhooks', [
            'url' => 'https://example.com/webhook',
            'events' => ['invalid.event']
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['events.0']);

        // Empty events array
        $response = $this->postJson('/api/v1/webhooks', [
            'url' => 'https://example.com/webhook',
            'events' => []
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['events']);

        // Secret too short
        $response = $this->postJson('/api/v1/webhooks', [
            'url' => 'https://example.com/webhook',
            'events' => ['analysis.completed'],
            'secret' => '1234567' // 7 chars, minimum is 8
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['secret']);
    }

    /** @test */
    public function it_can_create_a_webhook_successfully()
    {
        Sanctum::actingAs($this->user);

        $webhookData = [
            'name' => 'My Test Webhook',
            'description' => 'Webhook for testing SEO analysis notifications',
            'url' => 'https://example.com/webhook',
            'events' => ['analysis.completed', 'analysis.failed'],
            'secret' => 'my-secret-key-12345',
            'active' => true
        ];

        $response = $this->postJson('/api/v1/webhooks', $webhookData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'url',
                    'events',
                    'active',
                    'secret_configured',
                    'created_at',
                    'updated_at',
                    'delivery_stats'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Webhook created successfully',
                'data' => [
                    'name' => 'My Test Webhook',
                    'url' => 'https://example.com/webhook',
                    'events' => ['analysis.completed', 'analysis.failed'],
                    'active' => true,
                    'secret_configured' => true
                ]
            ]);

        // Verify webhook was created in database
        $this->assertDatabaseHas('webhooks', [
            'user_id' => $this->user->id,
            'name' => 'My Test Webhook',
            'url' => 'https://example.com/webhook',
            'active' => true
        ]);
    }

    /** @test */
    public function it_can_get_a_specific_webhook()
    {
        Sanctum::actingAs($this->user);

        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['analysis.completed']
        ]);

        $response = $this->getJson("/api/v1/webhooks/{$webhook->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Webhook retrieved successfully',
                'data' => [
                    'id' => $webhook->id,
                    'name' => 'Test Webhook',
                    'url' => 'https://example.com/webhook'
                ]
            ]);
    }

    /** @test */
    public function it_prevents_access_to_other_users_webhooks()
    {
        Sanctum::actingAs($this->user);

        $otherUser = User::factory()->create();
        $otherWebhook = Webhook::factory()->create([
            'user_id' => $otherUser->id
        ]);

        $response = $this->getJson("/api/v1/webhooks/{$otherWebhook->id}");
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'You do not have access to this webhook'
            ]);

        $response = $this->putJson("/api/v1/webhooks/{$otherWebhook->id}", [
            'url' => 'https://example.com/new-webhook',
            'events' => ['analysis.completed']
        ]);
        $response->assertStatus(403);

        $response = $this->deleteJson("/api/v1/webhooks/{$otherWebhook->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_update_a_webhook()
    {
        Sanctum::actingAs($this->user);

        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Original Name',
            'url' => 'https://example.com/original',
            'events' => ['analysis.completed'],
            'active' => true
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'url' => 'https://example.com/updated',
            'events' => ['analysis.completed', 'batch.completed'],
            'active' => false
        ];

        $response = $this->putJson("/api/v1/webhooks/{$webhook->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Webhook updated successfully',
                'data' => [
                    'id' => $webhook->id,
                    'name' => 'Updated Name',
                    'url' => 'https://example.com/updated',
                    'events' => ['analysis.completed', 'batch.completed'],
                    'active' => false
                ]
            ]);

        // Verify database was updated
        $this->assertDatabaseHas('webhooks', [
            'id' => $webhook->id,
            'name' => 'Updated Name',
            'url' => 'https://example.com/updated',
            'active' => false
        ]);
    }

    /** @test */
    public function it_can_delete_a_webhook()
    {
        Sanctum::actingAs($this->user);

        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson("/api/v1/webhooks/{$webhook->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Webhook deleted successfully'
            ]);

        // Verify webhook was deleted
        $this->assertDatabaseMissing('webhooks', [
            'id' => $webhook->id
        ]);
    }

    /** @test */
    public function it_can_test_a_webhook()
    {
        Sanctum::actingAs($this->user);

        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://httpbin.org/post',
            'secret' => 'test-secret'
        ]);

        // Mock HTTP response
        Http::fake([
            'httpbin.org/post' => Http::response(['success' => true], 200)
        ]);

        $response = $this->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'test_result' => [
                        'success',
                        'status_code',
                        'response_time_ms',
                        'delivered_at'
                    ],
                    'webhook'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Webhook test completed successfully',
                'data' => [
                    'test_result' => [
                        'success' => true,
                        'status_code' => 200
                    ]
                ]
            ]);

        // Verify HTTP request was made with correct headers
        Http::assertSent(function ($request) {
            return $request->hasHeader('Content-Type', 'application/json') &&
                   $request->hasHeader('User-Agent', 'SEO-Validator-Webhook/1.0') &&
                   $request->hasHeader('X-SEO-Validator-Event', 'test') &&
                   $request->hasHeader('X-SEO-Validator-Signature') &&
                   $request->url() === 'https://httpbin.org/post';
        });
    }

    /** @test */
    public function it_handles_webhook_test_failures()
    {
        Sanctum::actingAs($this->user);

        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://httpbin.org/status/500'
        ]);

        // Mock HTTP error response
        Http::fake([
            'httpbin.org/status/500' => Http::response(['error' => 'Internal Server Error'], 500)
        ]);

        $response = $this->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Webhook test failed',
                'errors' => [
                    'test_result' => [
                        'success' => false,
                        'status_code' => 500
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_validates_localhost_urls_in_production()
    {
        $this->app['env'] = 'production';
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/webhooks', [
            'url' => 'http://localhost:3000/webhook',
            'events' => ['analysis.completed']
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function it_requires_https_in_production()
    {
        $this->app['env'] = 'production';
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/webhooks', [
            'url' => 'http://example.com/webhook',
            'events' => ['analysis.completed']
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function it_prevents_duplicate_events_in_webhook()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/webhooks', [
            'url' => 'https://example.com/webhook',
            'events' => ['analysis.completed', 'analysis.completed']
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['events']);
    }

    /** @test */
    public function it_can_filter_webhooks_by_event_type()
    {
        Sanctum::actingAs($this->user);

        Webhook::factory()->create([
            'user_id' => $this->user->id,
            'events' => ['analysis.completed']
        ]);

        Webhook::factory()->create([
            'user_id' => $this->user->id,
            'events' => ['batch.completed']
        ]);

        $response = $this->getJson('/api/v1/webhooks?event=analysis.completed');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items');

        $response = $this->getJson('/api/v1/webhooks?event=batch.completed');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.items');
    }

    /** @test */
    public function it_updates_delivery_statistics_on_webhook_test()
    {
        Sanctum::actingAs($this->user);

        $webhook = Webhook::factory()->create([
            'user_id' => $this->user->id,
            'url' => 'https://httpbin.org/post'
        ]);

        Http::fake([
            'httpbin.org/post' => Http::response(['success' => true], 200)
        ]);

        $this->postJson("/api/v1/webhooks/{$webhook->id}/test");

        $webhook->refresh();

        $this->assertEquals(1, $webhook->total_deliveries);
        $this->assertEquals(1, $webhook->successful_deliveries);
        $this->assertEquals(0, $webhook->failed_deliveries);
        $this->assertNotNull($webhook->last_delivery_at);
        $this->assertEquals(200, $webhook->last_delivery_status_code);
        $this->assertTrue($webhook->last_delivery_success);
    }
}