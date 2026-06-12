<?php

namespace Tests\Unit;

use App\Models\StockOpname;
use App\Models\User;
use App\Policies\StockOpnamePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockOpnamePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $authorizedUser;
    protected User $unauthorizedUser;
    protected StockOpname $opname;

    protected function setUp(): void
    {
        parent::setUp();

        // Create the permission that the policy checks (uses 'sanctum' guard by default)
        $permission = Permission::create(['name' => 'edit stock_opnames']);

        // Create a role and assign the permission to it
        $role = Role::create(['name' => 'stock_opname_editor']);
        $role->givePermissionTo($permission);

        // Authorized user gets the role with the 'edit stock_opnames' permission
        $this->authorizedUser = User::factory()->create();
        $this->authorizedUser->assignRole($role);

        // Unauthorized user has no role/permission
        $this->unauthorizedUser = User::factory()->create();

        // Minimal StockOpname instance — policy methods only check the user, not the model
        $this->opname = new StockOpname();
    }

    /** @test */
    public function start_returns_true_for_authorized_user(): void
    {
        $policy = new StockOpnamePolicy();

        $result = $policy->start($this->authorizedUser, $this->opname);

        $this->assertTrue($result);
    }

    /** @test */
    public function submit_returns_true_for_authorized_user(): void
    {
        $policy = new StockOpnamePolicy();

        $result = $policy->submit($this->authorizedUser, $this->opname);

        $this->assertTrue($result);
    }

    /** @test */
    public function cancel_returns_true_for_authorized_user(): void
    {
        $policy = new StockOpnamePolicy();

        $result = $policy->cancel($this->authorizedUser, $this->opname);

        $this->assertTrue($result);
    }

    /** @test */
    public function start_returns_false_for_unauthorized_user(): void
    {
        $policy = new StockOpnamePolicy();

        $result = $policy->start($this->unauthorizedUser, $this->opname);

        $this->assertFalse($result);
    }
}
