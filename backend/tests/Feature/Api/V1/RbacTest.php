<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected User $staffUser;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the permissions that the policies check
        Permission::create(['name' => 'view settings', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'view users', 'guard_name' => 'sanctum']);
        Permission::create(['name' => 'view audit-logs', 'guard_name' => 'sanctum']);

        // Create staff role — no permissions at all
        Role::create(['name' => 'staff', 'guard_name' => 'sanctum']);

        // Create admin role with all the relevant permissions
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'sanctum']);
        $adminRole->givePermissionTo([
            'view settings',
            'view users',
            'view audit-logs',
        ]);

        // Staff user has the 'staff' role, which grants no permissions
        $this->staffUser = User::factory()->create();
        $this->staffUser->assignRole('staff');

        // Admin user has the 'admin' role with all required permissions
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');
    }

    /** @test */
    public function staff_cannot_view_settings()
    {
        $response = $this->actingAs($this->staffUser)
            ->getJson('/api/v1/settings');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_settings()
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/settings');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function staff_cannot_view_users()
    {
        $response = $this->actingAs($this->staffUser)
            ->getJson('/api/v1/users');

        $response->assertStatus(403);
    }

    /** @test */
    public function staff_cannot_view_audit_logs()
    {
        $response = $this->actingAs($this->staffUser)
            ->getJson('/api/v1/audit-logs');

        $response->assertStatus(403);
    }
}
