<?php

namespace Tests\Feature\Organization;

use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Organization $organization;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->user = User::factory()->create([
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
        $this->user->assignRole('org_admin');

        /** @var OrganizationService $service */
        $service = app(OrganizationService::class);

        $this->organization = $service->createOrganization($this->user, [
            'name'  => 'Test Org',
            'email' => $this->user->email,
        ]);

        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_user_can_list_their_organizations(): void
    {
        $response = $this->withToken($this->token)
                         ->getJson('/api/v1/organizations');

        $response->assertStatus(200)
                 ->assertJsonStructure(['organizations' => [['id', 'name', 'slug']]]);
    }

    public function test_user_can_create_additional_organization(): void
    {
        $response = $this->withToken($this->token)
                         ->postJson('/api/v1/organizations', [
                             'name' => 'Second Org',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('organization.name', 'Second Org');
    }

    public function test_org_admin_can_view_organization(): void
    {
        $slug = $this->organization->slug;

        $response = $this->withToken($this->token)
                         ->getJson("/api/v1/organizations/{$slug}");

        $response->assertStatus(200)
                 ->assertJsonPath('organization.name', 'Test Org');
    }

    public function test_org_admin_can_invite_member(): void
    {
        $slug = $this->organization->slug;

        $response = $this->withToken($this->token)
                         ->postJson("/api/v1/organizations/{$slug}/members/invite", [
                             'email' => 'newmember@example.com',
                             'role'  => 'store_manager',
                         ]);

        $response->assertStatus(201)
                 ->assertJsonPath('invitation.email', 'newmember@example.com')
                 ->assertJsonPath('invitation.status', 'pending');

        $this->assertDatabaseHas('user_invitations', [
            'email'  => 'newmember@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_non_member_cannot_access_organization(): void
    {
        $otherUser  = User::factory()->create(['is_active' => true]);
        $otherToken = $otherUser->createToken('test')->plainTextToken;
        $slug       = $this->organization->slug;

        $response = $this->withToken($otherToken)
                         ->getJson("/api/v1/organizations/{$slug}");

        $response->assertStatus(403);
    }

    public function test_org_admin_can_update_organization(): void
    {
        $slug = $this->organization->slug;

        $response = $this->withToken($this->token)
                         ->putJson("/api/v1/organizations/{$slug}", [
                             'name'  => 'Updated Org Name',
                             'email' => 'updated@example.com',
                         ]);

        $response->assertStatus(200)
                 ->assertJsonPath('organization.name', 'Updated Org Name');
    }

    public function test_org_admin_can_list_members(): void
    {
        $slug = $this->organization->slug;

        $response = $this->withToken($this->token)
                         ->getJson("/api/v1/organizations/{$slug}/members");

        $response->assertStatus(200)
                 ->assertJsonStructure(['members' => [['id', 'name', 'email']]]);
    }
}
