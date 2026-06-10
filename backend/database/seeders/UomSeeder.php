<?php

namespace Database\Seeders;

use App\Models\Uom;
use Illuminate\Database\Seeder;

class UomSeeder extends Seeder
{
    public function run(): void
    {
        $pcs = Uom::create([
            'code' => 'PCS',
            'name' => 'Pieces',
            'symbol' => 'pcs',
            'type' => 'unit',
        ]);

        $kg = Uom::create([
            'code' => 'KG',
            'name' => 'Kilogram',
            'symbol' => 'kg',
            'type' => 'weight',
        ]);

        $ltr = Uom::create([
            'code' => 'LTR',
            'name' => 'Liter',
            'symbol' => 'L',
            'type' => 'volume',
        ]);

        Uom::create([
            'code' => 'BOX',
            'name' => 'Box',
            'symbol' => 'box',
            'type' => 'unit',
            'conversion_factor' => 24,
            'base_uom_id' => $pcs->id,
        ]);

        Uom::create([
            'code' => 'ROLL',
            'name' => 'Roll',
            'symbol' => 'roll',
            'type' => 'unit',
        ]);

        Uom::create([
            'code' => 'MTR',
            'name' => 'Meter',
            'symbol' => 'm',
            'type' => 'length',
        ]);

        Uom::create([
            'code' => 'CM',
            'name' => 'Centimeter',
            'symbol' => 'cm',
            'type' => 'length',
            'conversion_factor' => 0.01,
            'base_uom_id' => Uom::where('code', 'MTR')->first()?->id,
        ]);

        Uom::create([
            'code' => 'TON',
            'name' => 'Ton',
            'symbol' => 'ton',
            'type' => 'weight',
            'conversion_factor' => 1000,
            'base_uom_id' => $kg->id,
        ]);

        Uom::create([
            'code' => 'ML',
            'name' => 'Milliliter',
            'symbol' => 'ml',
            'type' => 'volume',
            'conversion_factor' => 0.001,
            'base_uom_id' => $ltr->id,
        ]);

        $this->command->info('UOMs seeded: ' . Uom::count() . ' units');
    }
}
