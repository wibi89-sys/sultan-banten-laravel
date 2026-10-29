<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'akses_mutasi')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('akses_mutasi')->default(false)->after('akses_sidang_tpp');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'akses_mutasi')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('akses_mutasi');
            });
        }
    }
};
