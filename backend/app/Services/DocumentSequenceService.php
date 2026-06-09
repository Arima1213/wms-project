<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DocumentSequenceService
{
    /**
     * Generate nomor dokumen dengan format: {PREFIX}-YYYYMMDD-XXXXXX
     * Menggunakan DB row lock + upsert untuk menjamin uniqueness tanpa collision.
     *
     * @param string $prefix  Kode prefix, misal: 'INB', 'OUT', 'TRF', 'SO'
     * @return string         Contoh: INB-20260610-000001
     */
    public function getNextNumber(string $prefix = 'DOC'): string
    {
        $today = now()->toDateString();
        $dateYmd = now()->format('Ymd');

        return DB::transaction(function () use ($prefix, $today, $dateYmd) {
            // Lock row untuk prefix+date hari ini (kalau belum ada, ga masalah — akan di-insert)
            // Gunakan FOR UPDATE untuk row lock
            $sequence = DB::table('document_sequences')
                ->where('prefix', $prefix)
                ->where('date', $today)
                ->lockForUpdate()
                ->first();

            if ($sequence) {
                // Sudah ada — increment
                $newCounter = $sequence->counter + 1;
                DB::table('document_sequences')
                    ->where('id', $sequence->id)
                    ->update([
                        'counter' => $newCounter,
                        'updated_at' => now(),
                    ]);
            } else {
                // Belum ada — insert dengan counter 1
                $newCounter = 1;
                DB::table('document_sequences')->insert([
                    'prefix' => $prefix,
                    'date' => $today,
                    'counter' => $newCounter,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Format: PREFIX-YYYYMMDD-XXXXXX (6 digit counter)
            return $prefix . '-' . $dateYmd . '-' . str_pad($newCounter, 6, '0', STR_PAD_LEFT);
        });
    }
}
