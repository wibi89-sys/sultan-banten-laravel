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
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'akses_sidang_tpp_kanwil')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'akses_sidang_tpp')) {
                    $table->string('akses_sidang_tpp_kanwil', 10)->default('none')->after('akses_sidang_tpp');
                } else {
                    $table->string('akses_sidang_tpp_kanwil', 10)->default('none');
                }
            });

            // Berikan izin penuh pada akun Administrator dan Operator Wilayah (Kanwil)
            DB::table('users')->whereIn('role', ['admin', 'operator_wilayah'])->update(['akses_sidang_tpp_kanwil' => 'full']);
            // Pastikan akun UPT (role operator / terikat pada UPT) tetap 'none'
            DB::table('users')->where('role', 'operator')->orWhereNotNull('upt_id')->update(['akses_sidang_tpp_kanwil' => 'none']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'akses_sidang_tpp_kanwil')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('akses_sidang_tpp_kanwil');
            });
        }
    }
};
