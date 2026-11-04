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
            if (! Schema::hasColumn('wa_settings', 'wa_notif_sidang_tpp_otomatis')) {
                $table->boolean('wa_notif_sidang_tpp_otomatis')->default(false)->after('group_id_tpp');
            }
            if (! Schema::hasColumn('wa_settings', 'wa_notif_mutasi_otomatis')) {
                $table->boolean('wa_notif_mutasi_otomatis')->default(false)->after('wa_notif_sidang_tpp_otomatis');
            }
            if (! Schema::hasColumn('wa_settings', 'group_id_mutasi')) {
                $table->string('group_id_mutasi', 100)->nullable()->after('wa_notif_mutasi_otomatis');
            }
            if (! Schema::hasColumn('wa_settings', 'template_mutasi_usulan')) {
                $table->text('template_mutasi_usulan')->nullable()->after('group_id_mutasi');
            }
            if (! Schema::hasColumn('wa_settings', 'template_mutasi_disetujui')) {
                $table->text('template_mutasi_disetujui')->nullable()->after('template_mutasi_usulan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_settings', function (Blueprint $table) {
            $columns = [
                'wa_notif_sidang_tpp_otomatis',
                'wa_notif_mutasi_otomatis',
                'group_id_mutasi',
                'template_mutasi_usulan',
                'template_mutasi_disetujui',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('wa_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
