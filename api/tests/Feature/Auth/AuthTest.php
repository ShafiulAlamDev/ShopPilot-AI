<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    public function test_user_can_register_with_organization(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'organization_name'     => 'Acme Corp',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'user'         => ['id', 'name', 'email'],
                     'organization' => ['id', 'name', 'slug'],
                     'token',
                 ]);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $this->assertDatabaseHas('organizations', ['name' => 'Acme Corp']);
        $this->assertDatabaseHas('stores', ['name' => 'Acme Corp Store']);
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'password'  => bcrypt('password123'),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['message', 'user', 'token']);
    }

    public function test_login_fails_with_wrong_credentials(): void
    {
        $user = User::factory()->create(['password' => bcrypt('correct-password')]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'password'  => bcrypt('password'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_logout(): void
    {
        $user  = User::factory()->create(['is_active' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Logged out successfully.']);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user  = User::factory()->create(['is_active' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
                 ->assertJsonPath('user.email', $user->email);
    }

    public function test_register_validation_fails_on_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Another',
            'email'                 => 'taken@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'organization_name'     => 'Another Org',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }
}
