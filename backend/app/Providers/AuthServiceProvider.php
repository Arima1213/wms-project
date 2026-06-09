<?php

namespace App\Providers;

use App\Models\Inbound;
use App\Models\Outbound;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Zone;
use App\Models\Rack;
use App\Models\RackSlot;
use App\Models\Category;
use App\Models\Transfer;
use App\Models\StockOpname;
use App\Models\Planogram;
use App\Models\Document;
use App\Models\Inventory;
use App\Models\User;
use App\Policies\InboundPolicy;
use App\Policies\OutboundPolicy;
use App\Policies\WarehousePolicy;
use App\Policies\TransferPolicy;
use App\Policies\StockOpnamePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Inbound::class => InboundPolicy::class,
        Outbound::class => OutboundPolicy::class,
        Warehouse::class => WarehousePolicy::class,
        Transfer::class => TransferPolicy::class,
        StockOpname::class => StockOpnamePolicy::class,
        Product::class => ProductPolicy::class,
        Zone::class => ZonePolicy::class,
        Rack::class => RackPolicy::class,
        RackSlot::class => RackSlotPolicy::class,
        Category::class => CategoryPolicy::class,
        Planogram::class => PlanogramPolicy::class,
        Document::class => DocumentPolicy::class,
        Inventory::class => InventoryPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Super admin gets all permissions
        Gate::before(function (User $user) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
        });
    }
}
