<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ubah kolom role dari ENUM menjadi VARCHAR(30) agar fleksibel mendukung operator_wilayah
        try {
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` VARCHAR(30) NOT NULL DEFAULT 'operator'");
        } catch (Throwable $e) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 30)->default('operator')->change();
            });
        }

        // Pastikan seluruh akun admin lama memiliki permission full
        $permissionFields = [
            'akses_dashboard',
            'akses_penghuni',
            'akses_overstaying',
            'akses_integrasi',
            'akses_bebas_harian',
            'akses_sidang_tpp',
            'akses_mutasi',
            'akses_upt',
            'akses_user',
            'akses_instansi',
            'akses_laporan',
            'akses_pengumuman',
            'akses_wa_settings',
            'akses_wa_schedule',
            'akses_activity_log',
        ];

        $adminUpdates = [];
        foreach ($permissionFields as $col) {
            if (Schema::hasColumn('users', $col)) {
                $adminUpdates[$col] = 'full';
            }
        }
        if (! empty($adminUpdates)) {
            DB::table('users')->where('role', 'admin')->update($adminUpdates);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to enum if necessary
        try {
            DB::statement("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin', 'operator', 'viewer') NOT NULL DEFAULT 'operator'");
        } catch (Throwable $e) {
            // ignore
        }
    }
};
