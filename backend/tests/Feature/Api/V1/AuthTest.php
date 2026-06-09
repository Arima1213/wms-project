<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected string $password = 'password123';

    /**
     * Helper to create a user.
     */
    protected function createUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'password' => Hash::make($this->password),
        ], $overrides));
    }

    /** @test */
    public function user_can_register()
    {
        $response = $this->postJson('/api/v1/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    /** @test */
    public function user_cannot_register_with_duplicate_email()
    {
        $this->createUser(['email' => 'test@example.com']);

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Another User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function user_can_login_with_valid_credentials()
    {
        $this->createUser(['email' => 'user@example.com']);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'user@example.com',
            'password' => $this->password,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email'],
                'token',
            ]);
    }

    /** @test */
    public function user_cannot_login_with_invalid_credentials()
    {
        $this->createUser(['email' => 'user@example.com']);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Invalid credentials']);
    }

    /** @test */
    public function inactive_user_cannot_login()
    {
        $this->createUser([
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'inactive@example.com',
            'password' => $this->password,
        ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Account is deactivated']);
    }

    /** @test */
    public function authenticated_user_can_get_their_profile()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    /** @test */
    public function unauthenticated_user_cannot_get_profile()
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401);
    }

    /** @test */
    public function authenticated_user_can_logout()
    {
        $user = $this->createUser();
        $token = $user->createToken('api-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out']);

        // Token should be deleted
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /** @test */
    public function user_cannot_access_protected_routes_without_token()
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401);
    }
}
