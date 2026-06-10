<?php

namespace Database\Seeders;

use App\Models\Planogram;
use App\Models\PlanogramSnapshot;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class PlanogramSeeder extends Seeder
{
    public function run(): void
    {
        $wh1 = Warehouse::where('code', 'WH001')->first();
        $wh2 = Warehouse::where('code', 'WH002')->first();
        $admin = User::where('email', 'admin@wms.local')->first();

        if (!$wh1 || !$wh2) return;

        // ─── Planogram WH001 ────────────────────────────────────────
        $planogram1 = Planogram::create([
            'warehouse_id' => $wh1->id,
            'canvas_width' => 5000,
            'canvas_height' => 3000,
            'grid_size' => 50,
            'version' => '1.0',
            'canvas_data' => [
                'zones' => [
                    ['code' => 'A', 'name' => 'Fast Moving', 'x' => 50, 'y' => 50, 'width' => 800, 'height' => 500],
                    ['code' => 'B', 'name' => 'Slow Moving', 'x' => 50, 'y' => 600, 'width' => 600, 'height' => 400],
                    ['code' => 'C', 'name' => 'Heavy', 'x' => 50, 'y' => 1050, 'width' => 600, 'height' => 300],
                ],
            ],
            'canvas_settings' => [
                'show_grid' => true,
                'snap_to_grid' => true,
                'background_color' => '#f8fafc',
            ],
            'change_summary' => 'Initial planogram layout',
            'created_by' => $admin?->id,
        ]);

        // Snapshot for WH001
        PlanogramSnapshot::create([
            'planogram_id' => $planogram1->id,
            'version' => '1.0',
            'canvas_data' => $planogram1->canvas_data,
            'change_summary' => 'Initial snapshot',
            'created_by' => $admin?->id,
        ]);

        // ─── Planogram WH002 ────────────────────────────────────────
        $planogram2 = Planogram::create([
            'warehouse_id' => $wh2->id,
            'canvas_width' => 4000,
            'canvas_height' => 2000,
            'grid_size' => 50,
            'version' => '1.0',
            'canvas_data' => [
                'zones' => [
                    ['code' => 'A', 'name' => 'Utama', 'x' => 50, 'y' => 50, 'width' => 600, 'height' => 400],
                ],
            ],
            'change_summary' => 'Initial planogram layout',
            'created_by' => $admin?->id,
        ]);

        $this->command->info('Planograms seeded: 2 planograms with snapshots');
    }
}
