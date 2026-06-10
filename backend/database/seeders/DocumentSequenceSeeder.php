<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentSequenceSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $sequences = [
            ['prefix' => 'INB', 'date' => $now->format('Y-m-d'), 'counter' => 3],
            ['prefix' => 'OUT', 'date' => $now->format('Y-m-d'), 'counter' => 3],
            ['prefix' => 'TRF', 'date' => $now->format('Y-m-d'), 'counter' => 2],
            ['prefix' => 'SO',  'date' => $now->format('Y-m-d'), 'counter' => 2],
        ];

        foreach ($sequences as $seq) {
            DB::table('document_sequences')->insert($seq);
        }

        $this->command->info('Document sequences seeded: 4 sequences');
    }
}
