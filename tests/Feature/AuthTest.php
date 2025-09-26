<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test user registration endpoint.
     */
    public function test_user_can_register(): void
    {
        $userData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'user' => ['id', 'name', 'email'],
                     'token',
                     'token_type',
                 ]);

        $this->assertDatabaseHas('users', [
            'name' => $userData['name'],
            'email' => $userData['email'],
        ]);
    }

    /**
     * Test user registration validation.
     */
    public function test_user_registration_requires_valid_data(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * Test user registration with duplicate email.
     */
    public function test_user_registration_prevents_duplicate_email(): void
    {
        $user = User::factory()->create();

        $userData = [
            'name' => $this->faker->name,
            'email' => $user->email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test user login endpoint.
     */
    public function test_user_can_login(): void
    {
        $password = 'Password123!';
        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        $loginData = [
            'email' => $user->email,
            'password' => $password,
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'user' => ['id', 'name', 'email'],
                     'token',
                     'token_type',
                 ]);
    }

    /**
     * Test user login with invalid credentials.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $loginData = [
            'email' => $user->email,
            'password' => 'wrong-password',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test authenticated user can access protected routes.
     */
    public function test_authenticated_user_can_access_protected_routes(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->getJson('/api/auth/user', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'user' => [
                         'id' => $user->id,
                         'email' => $user->email,
                     ],
                 ]);
    }

    /**
     * Test user can logout and token is revoked.
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->postJson('/api/auth/logout', [], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'Successfully logged out',
                 ]);

        // Verify token is revoked by trying to access protected route
        $response = $this->getJson('/api/auth/user', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test user can revoke all tokens.
     */
    public function test_user_can_revoke_all_tokens(): void
    {
        $user = User::factory()->create();
        $token1 = $user->createToken('token-1')->plainTextToken;
        $token2 = $user->createToken('token-2')->plainTextToken;

        $response = $this->postJson('/api/auth/revoke-all-tokens', [], [
            'Authorization' => 'Bearer ' . $token1,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'message' => 'All tokens have been revoked',
                 ]);

        // Verify all tokens are revoked
        $response1 = $this->getJson('/api/auth/user', [
            'Authorization' => 'Bearer ' . $token1,
        ]);

        $response2 = $this->getJson('/api/auth/user', [
            'Authorization' => 'Bearer ' . $token2,
        ]);

        $response1->assertStatus(401);
        $response2->assertStatus(401);
    }

    /**
     * Test API rate limiting on auth endpoints.
     */
    public function test_auth_endpoints_are_rate_limited(): void
    {
        $loginData = [
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        // Make 6 requests to exceed the 5 per minute limit
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/login', $loginData);
            if ($i < 5) {
                // First 5 should be processed (will fail auth but not rate limited)
                $response->assertStatus(422);
            } else {
                // 6th request should be rate limited
                $response->assertStatus(429);
            }
        }
    }
}