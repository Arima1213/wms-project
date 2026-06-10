<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('╔══════════════════════════════════════════════╗');
        $this->command->info('║        WMS Database Seeding Started         ║');
        $this->command->info('╚══════════════════════════════════════════════╝');

        // ─── Core Master Data (no dependencies) ────────────────
        $this->call(RoleAndPermissionSeeder::class);

        // ─── Master Data (depend on roles/permissions) ─────────
        $this->call(UserSeeder::class);
        $this->call(UomSeeder::class);
        $this->call(ProductCategorySeeder::class);

        // ─── Warehouse & Infrastructure ────────────────────────
        $this->call(WarehouseSeeder::class);
        $this->call(ProductSeeder::class);
        $this->call(SupplierSeeder::class);
        $this->call(CustomerSeeder::class);

        // ─── Inventory Data ────────────────────────────────────
        $this->call(ProductBatchSeeder::class);
        $this->call(SlotStockSeeder::class);
        $this->call(InventorySeeder::class);

        // ─── Transactional Documents ───────────────────────────
        $this->call(InboundSeeder::class);
        $this->call(OutboundSeeder::class);
        $this->call(TransferSeeder::class);
        $this->call(StockOpnameSeeder::class);

        // ─── Supporting Features ───────────────────────────────
        $this->call(SettingSeeder::class);
        $this->call(PlanogramSeeder::class);
        $this->call(DocumentSequenceSeeder::class);
        $this->call(WebhookSeeder::class);
        $this->call(AuditLogSeeder::class);

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════╗');
        $this->command->info('║   WMS Database Seeded Successfully!         ║');
        $this->command->info('╠══════════════════════════════════════════════╣');
        $this->command->info('║   Admin:    admin@wms.local / password123   ║');
        $this->command->info('║   Manager:  budi@wms.local  / password123   ║');
        $this->command->info('║   Manager:  dewi@wms.local  / password123   ║');
        $this->command->info('║   Operator: siti@wms.local  / password123   ║');
        $this->command->info('║   Operator: ahmad@wms.local / password123   ║');
        $this->command->info('║   Operator: rina@wms.local  / password123   ║');
        $this->command->info('║   Viewer:   viewer@wms.local / password123   ║');
        $this->command->info('╚══════════════════════════════════════════════╝');
    }
}
