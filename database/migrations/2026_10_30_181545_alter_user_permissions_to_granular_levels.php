<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissionFields = [
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

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ($this->permissionFields as $field) {
                if (Schema::hasColumn('users', $field)) {
                    $table->string($field, 10)->default('none')->change();
                } else {
                    $table->string($field, 10)->default('none');
                }
            }
        });

        // Konversi nilai boolean lama (1 -> full, 0 -> none)
        foreach ($this->permissionFields as $field) {
            DB::table('users')->where($field, '1')->update([$field => 'full']);
            DB::table('users')->where($field, '0')->update([$field => 'none']);
            DB::table('users')->whereNull($field)->update([$field => 'none']);
        }

        // Untuk role viewer lama yang aktif, sesuaikan menjadi 'view'
        foreach ($this->permissionFields as $field) {
            DB::table('users')
                ->where('role', 'viewer')
                ->where($field, 'full')
                ->update([$field => 'view']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->permissionFields as $field) {
            DB::table('users')->whereIn($field, ['view', 'full'])->update([$field => '1']);
            DB::table('users')->where($field, 'none')->update([$field => '0']);
        }

        Schema::table('users', function (Blueprint $table) {
            foreach ($this->permissionFields as $field) {
                $table->boolean($field)->default(false)->change();
            }
        });
    }
};
