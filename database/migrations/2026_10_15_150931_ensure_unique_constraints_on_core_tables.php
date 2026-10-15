<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Data Overstaying
        if (Schema::hasTable('data_overstaying')) {
            // Hapus duplikasi jika ada (simpan record terbaru / id terbesar)
            DB::statement('
                DELETE d1 FROM data_overstaying d1
                INNER JOIN data_overstaying d2
                WHERE d1.id < d2.id
                  AND d1.upt_id = d2.upt_id
                  AND d1.tanggal = d2.tanggal
            ');

            $this->ensureUniqueIndex('data_overstaying', 'idx_upt_tanggal', ['upt_id', 'tanggal']);
        }

        // 2. Data Integrasi
        if (Schema::hasTable('data_integrasi')) {
            DB::statement('
                DELETE d1 FROM data_integrasi d1
                INNER JOIN data_integrasi d2
                WHERE d1.id < d2.id
                  AND d1.upt_id = d2.upt_id
                  AND d1.tanggal = d2.tanggal
            ');

            $this->ensureUniqueIndex('data_integrasi', 'idx_upt_tanggal', ['upt_id', 'tanggal']);
        }

        // 3. Data Bebas Harian
        if (Schema::hasTable('data_bebas_harian')) {
            DB::statement('
                DELETE d1 FROM data_bebas_harian d1
                INNER JOIN data_bebas_harian d2
                WHERE d1.id < d2.id
                  AND d1.upt_id = d2.upt_id
                  AND d1.tanggal = d2.tanggal
                  AND d1.klasifikasi_pidana = d2.klasifikasi_pidana
            ');

            $this->ensureUniqueIndex('data_bebas_harian', 'unique_bebas_upt_tgl_klasifikasi', ['upt_id', 'tanggal', 'klasifikasi_pidana']);
        }

        // 4. Data Penghuni
        if (Schema::hasTable('data_penghuni')) {
            DB::statement('
                DELETE d1 FROM data_penghuni d1
                INNER JOIN data_penghuni d2
                WHERE d1.id < d2.id
                  AND d1.upt_id = d2.upt_id
                  AND d1.tanggal = d2.tanggal
                  AND d1.klasifikasi_pidana = d2.klasifikasi_pidana
            ');

            $this->ensureUniqueIndex('data_penghuni', 'unique_upt_tgl_klasifikasi', ['upt_id', 'tanggal', 'klasifikasi_pidana']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tetap biarkan indeks unik demi integritas data
    }

    /**
     * Helper to ensure an index is strictly UNIQUE on a table.
     *
     * @param  array<string>  $columns
     */
    private function ensureUniqueIndex(string $table, string $indexName, array $columns): void
    {
        $existing = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'");

        if (! empty($existing)) {
            $isUnique = ((int) $existing[0]->Non_unique === 0);
            if ($isUnique) {
                return;
            }

            // Indeks ada tetapi bukan UNIQUE, kita drop lalu buat ulang sebagai UNIQUE
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        }

        $columnList = implode(', ', array_map(fn ($col) => "`{$col}`", $columns));
        DB::statement("ALTER TABLE `{$table}` ADD UNIQUE KEY `{$indexName}` ({$columnList})");
    }
};
