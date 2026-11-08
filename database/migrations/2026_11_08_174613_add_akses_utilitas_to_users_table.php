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
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'akses_utilitas')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'akses_mutasi')) {
                    $table->string('akses_utilitas', 10)->default('none')->after('akses_mutasi');
                } else {
                    $table->string('akses_utilitas', 10)->default('none');
                }
            });

            // Berikan izin penuh pada admin/operator eksisting dan izin lihat pada viewer
            DB::table('users')->whereIn('role', ['admin', 'operator_wilayah', 'operator'])->update(['akses_utilitas' => 'full']);
            DB::table('users')->where('role', 'viewer')->update(['akses_utilitas' => 'view']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'akses_utilitas')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('akses_utilitas');
            });
        }
    }
};
