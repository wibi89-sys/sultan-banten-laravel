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
        if (Schema::hasTable('sidang_tpp')) {
            Schema::table('sidang_tpp', function (Blueprint $table) {
                if (! Schema::hasColumn('sidang_tpp', 'total_tunda')) {
                    $table->integer('total_tunda')->default(0)->after('total_setuju');
                }
            });

            DB::statement('UPDATE `sidang_tpp` SET `total_tunda` = `total_ditunda`');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('sidang_tpp')) {
            Schema::table('sidang_tpp', function (Blueprint $table) {
                if (Schema::hasColumn('sidang_tpp', 'total_tunda')) {
                    $table->dropColumn('total_tunda');
                }
            });
        }
    }
};
