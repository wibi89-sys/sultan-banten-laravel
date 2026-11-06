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
            if (! Schema::hasColumn('wa_settings', 'template_pengingat_sidang_ba')) {
                $table->text('template_pengingat_sidang_ba')->nullable()->after('template_sidang_tpp');
            }
            if (! Schema::hasColumn('wa_settings', 'template_pengingat_mutasi')) {
                $table->text('template_pengingat_mutasi')->nullable()->after('template_mutasi_disetujui');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_settings', function (Blueprint $table) {
            if (Schema::hasColumn('wa_settings', 'template_pengingat_sidang_ba')) {
                $table->dropColumn('template_pengingat_sidang_ba');
            }
            if (Schema::hasColumn('wa_settings', 'template_pengingat_mutasi')) {
                $table->dropColumn('template_pengingat_mutasi');
            }
        });
    }
};
