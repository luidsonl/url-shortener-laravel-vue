<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ShortLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.guards.api.driver' => 'sanctum']);
    }

    protected function loginAndGetToken($email, $password = 'password')
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => $password
        ]);

        return $response->json('access_token');
    }

    public function test_unauthenticated_user_cannot_access_shortlink_routes()
    {
        $shortLink = ShortLink::factory()->create();
        
        $routes = [
            ['method' => 'GET', 'url' => '/api/short-links'],
            ['method' => 'POST', 'url' => '/api/short-links'],
            ['method' => 'GET', 'url' => "/api/short-links/{$shortLink->id}"],
            ['method' => 'PUT', 'url' => "/api/short-links/{$shortLink->id}"],
            ['method' => 'DELETE', 'url' => "/api/short-links/{$shortLink->id}"],
            ['method' => 'POST', 'url' => '/api/short-links/bulk-delete'],
        ];

        foreach ($routes as $route) {
            $response = $this->json($route['method'], $route['url']);
            $response->assertStatus(401);
        }
    }

    public function test_unverified_user_cannot_access_shortlink_routes()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => null,
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');
        
        $shortLink = ShortLink::factory()->create(['user_id' => $user->id]);

        $routes = [
            ['method' => 'GET', 'url' => '/api/short-links', 'name' => 'List short links'],
            ['method' => 'POST', 'url' => '/api/short-links', 'name' => 'Create short link', 'data' => [
                'original_url' => 'https://example.com',
            ]],
            ['method' => 'GET', 'url' => "/api/short-links/{$shortLink->id}", 'name' => 'Show short link'],
            ['method' => 'PUT', 'url' => "/api/short-links/{$shortLink->id}", 'name' => 'Update short link', 'data' => [
                'original_url' => 'https://updated.com',
            ]],
            ['method' => 'DELETE', 'url' => "/api/short-links/{$shortLink->id}", 'name' => 'Delete short link'],
            ['method' => 'POST', 'url' => '/api/short-links/bulk-delete', 'name' => 'Bulk delete', 'data' => [
                'ids' => [$shortLink->id]
            ]],
        ];

        foreach ($routes as $route) {
            $method = $route['method'];
            $url = $route['url'];
            
            if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $response = $this->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->json($method, $url, $route['data'] ?? []);
            } else {
                $response = $this->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->json($method, $url);
            }

            $response->assertStatus(403)
                ->assertJson(['message' => 'Unauthorized access']);
        }
    }

    public function test_unverified_admin_cannot_access_shortlink_routes()
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'email_verified_at' => null,
        ]);
        
        $token = $this->loginAndGetToken('admin@example.com');
        
        $shortLink = ShortLink::factory()->create(['user_id' => $admin->id]);

        $routes = [
            ['method' => 'GET', 'url' => '/api/short-links', 'name' => 'List short links'],
            ['method' => 'POST', 'url' => '/api/short-links', 'name' => 'Create short link', 'data' => [
                'original_url' => 'https://example.com',
            ]],
            ['method' => 'GET', 'url' => "/api/short-links/{$shortLink->id}", 'name' => 'Show short link'],
        ];

        foreach ($routes as $route) {
            $method = $route['method'];
            $url = $route['url'];
            
            if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $response = $this->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->json($method, $url, $route['data'] ?? []);
            } else {
                $response = $this->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->json($method, $url);
            }

            $response->assertStatus(403)
                ->assertJson(['message' => 'Unauthorized access']);
        }
    }

    public function test_verified_user_can_list_their_own_short_links()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        ShortLink::factory()->count(3)->create(['user_id' => $user->id]);
        ShortLink::factory()->count(2)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/short-links');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'original_url',
                        'short_code',
                        'visits_count',
                        'expires_at',
                        'created_at',
                        'updated_at'
                    ]
                ],
                'links',
                'meta'
            ]);

        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    public function test_verified_user_can_create_short_link()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        $shortLinkData = [
            'original_url' => 'https://example.com/test',
            'expires_at' => now()->addDays(7)->format('Y-m-d H:i:s'),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/short-links', $shortLinkData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'id',
                'original_url',
                'short_code',
                'visits_count',
                'expires_at'
            ])
            ->assertJson([
                'message' => 'Short link created',
                'original_url' => 'https://example.com/test',
                'visits_count' => 0
            ]);

        $this->assertDatabaseHas('short_links', [
            'user_id' => $user->id,
            'original_url' => 'https://example.com/test',
            'clicks' => 0
        ]);
    }

    public function test_verified_user_can_view_their_own_short_link()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        $shortLink = ShortLink::factory()->create([
            'user_id' => $user->id,
            'original_url' => 'https://example.com/test',
            'code' => 'abc123',
            'clicks' => 0,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/short-links/{$shortLink->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $shortLink->id,
                'original_url' => 'https://example.com/test',
                'short_code' => 'abc123',
                'visits_count' => 0
            ]);
    }

    public function test_verified_user_cannot_view_other_users_short_link()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        $otherUser = User::factory()->create();
        $shortLink = ShortLink::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/short-links/{$shortLink->id}");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized']);
    }

    public function test_verified_user_can_update_their_own_short_link()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        $shortLink = ShortLink::factory()->create([
            'user_id' => $user->id,
            'original_url' => 'https://old.com'
        ]);

        $updateData = [
            'original_url' => 'https://new.com',
            'expires_at' => now()->addDays(30)->format('Y-m-d H:i:s'),
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/short-links/{$shortLink->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Short link updated',
                'id' => $shortLink->id,
                'original_url' => 'https://new.com'
            ]);

        $this->assertDatabaseHas('short_links', [
            'id' => $shortLink->id,
            'original_url' => 'https://new.com'
        ]);
    }

    public function test_verified_user_cannot_update_other_users_short_link()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        $otherUser = User::factory()->create();
        $shortLink = ShortLink::factory()->create(['user_id' => $otherUser->id]);

        $updateData = [
            'original_url' => 'https://hacked.com',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson("/api/short-links/{$shortLink->id}", $updateData);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized']);

        $this->assertDatabaseMissing('short_links', [
            'id' => $shortLink->id,
            'original_url' => 'https://hacked.com'
        ]);
    }

    public function test_verified_user_can_delete_their_own_short_link()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        $shortLink = ShortLink::factory()->create(['user_id' => $user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/short-links/{$shortLink->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('short_links', ['id' => $shortLink->id]);
    }

    public function test_verified_user_cannot_delete_other_users_short_link()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        $otherUser = User::factory()->create();
        $shortLink = ShortLink::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/short-links/{$shortLink->id}");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Unauthorized']);

        $this->assertDatabaseHas('short_links', ['id' => $shortLink->id]);
    }

    public function test_verified_user_can_bulk_delete_their_own_short_links()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        $shortLinks = ShortLink::factory()->count(3)->create(['user_id' => $user->id]);
        $otherUserLinks = ShortLink::factory()->count(2)->create();

        $ids = $shortLinks->pluck('id')->toArray();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/short-links/bulk-delete', [
            'ids' => $ids
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => '3 links deleted']);

        foreach ($shortLinks as $link) {
            $this->assertDatabaseMissing('short_links', ['id' => $link->id]);
        }

        foreach ($otherUserLinks as $link) {
            $this->assertDatabaseHas('short_links', ['id' => $link->id]);
        }
    }

    public function test_verified_user_cannot_bulk_delete_other_users_short_links()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        $otherUser = User::factory()->create();
        $otherUserLinks = ShortLink::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $ids = $otherUserLinks->pluck('id')->toArray();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/short-links/bulk-delete', [
            'ids' => $ids
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['ids.0', 'ids.1']);

        foreach ($otherUserLinks as $link) {
            $this->assertDatabaseHas('short_links', ['id' => $link->id]);
        }
    }

    public function test_create_short_link_validates_required_fields()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/short-links', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['original_url']);
    }

    public function test_create_short_link_validates_url_format()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/short-links', [
            'original_url' => 'not-a-valid-url'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['original_url']);
    }

    public function test_create_short_link_validates_expires_at_date()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/short-links', [
            'original_url' => 'https://example.com',
            'expires_at' => 'invalid-date'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['expires_at']);
    }

    public function test_create_short_link_validates_expires_at_not_in_past()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);
        
        $token = $this->loginAndGetToken('user@example.com');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/short-links', [
            'original_url' => 'https://example.com',
            'expires_at' => now()->subDay()->format('Y-m-d H:i:s')
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['expires_at']);
    }

    public function test_public_user_can_redirect_with_valid_short_link()
    {
        $shortLink = ShortLink::factory()->create([
            'original_url' => 'https://example.com',
            'code' => 'abc123',
            'expires_at' => now()->addDays(7)
        ]);

        $response = $this->get("/{$shortLink->code}");

        $response->assertRedirect('https://example.com');

        $this->assertDatabaseHas('short_links', [
            'id' => $shortLink->id,
        ]);
    }

    public function test_public_redirect_returns_404_for_invalid_code()
    {
        $response = $this->get('/nonexistentcode');

        $response->assertStatus(404)
            ->assertJson(['message' => 'Link not found']);
    }

    public function test_public_redirect_returns_410_for_expired_link()
    {
        $shortLink = ShortLink::factory()->create([
            'original_url' => 'https://example.com',
            'code' => 'expired',
            'expires_at' => now()->subDay()
        ]);

        $response = $this->get("/{$shortLink->code}");

        $response->assertStatus(410)
            ->assertJson(['message' => 'Link expired']);
    }

    public function test_verified_admin_can_access_shortlink_routes()
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
        ]);
        
        $token = $this->loginAndGetToken('admin@example.com');

        $shortLink = ShortLink::factory()->create(['user_id' => $admin->id]);

        $routes = [
            ['method' => 'GET', 'url' => '/api/short-links'],
            ['method' => 'POST', 'url' => '/api/short-links'],
            ['method' => 'GET', 'url' => "/api/short-links/{$shortLink->id}"],
            ['method' => 'PUT', 'url' => "/api/short-links/{$shortLink->id}"],
            ['method' => 'DELETE', 'url' => "/api/short-links/{$shortLink->id}"],
        ];

        foreach ($routes as $route) {
            $method = $route['method'];
            
            if ($method === 'POST') {
                $response = $this->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->json($method, $route['url'], [
                    'original_url' => 'https://example.com'
                ]);
                $response->assertStatus(201);
            } elseif ($method === 'GET') {
                $response = $this->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->json($method, $route['url']);
                $response->assertStatus(200);
            } elseif ($method === 'PUT') {
                $response = $this->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->json($method, $route['url'], [
                    'original_url' => 'https://updated.com'
                ]);
                $response->assertStatus(200);
            } elseif ($method === 'DELETE') {
                $response = $this->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                ])->json($method, $route['url']);
                $response->assertStatus(200);
            }
        }
    }
}