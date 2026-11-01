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
        Schema::table('wa_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('wa_settings', 'template_sidang_tpp')) {
                $table->text('template_sidang_tpp')->nullable()->after('template_siang');
            }
            if (! Schema::hasColumn('wa_settings', 'group_id_tpp')) {
                $table->string('group_id_tpp', 100)->nullable()->after('template_sidang_tpp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_settings', function (Blueprint $table) {
            if (Schema::hasColumn('wa_settings', 'template_sidang_tpp')) {
                $table->dropColumn('template_sidang_tpp');
            }
            if (Schema::hasColumn('wa_settings', 'group_id_tpp')) {
                $table->dropColumn('group_id_tpp');
            }
        });
    }
};
