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
        Schema::table('wa_notification_logs', function (Blueprint $table) {
            $table->index(['tipe', 'tanggal_kirim'], 'idx_wa_logs_tipe_tanggal');
            $table->index('tanggal_kirim', 'idx_wa_logs_tanggal');
        });

        Schema::table('wa_scheduled_reports', function (Blueprint $table) {
            $table->index(['aktif', 'jam_kirim'], 'idx_wa_reports_aktif_jam');
        });

        Schema::table('pengumuman', function (Blueprint $table) {
            $table->index(['aktif', 'tampilkan_marquee'], 'idx_pengumuman_aktif_marquee');
            $table->index(['tanggal_mulai', 'tanggal_selesai'], 'idx_pengumuman_dates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_notification_logs', function (Blueprint $table) {
            $table->dropIndex('idx_wa_logs_tipe_tanggal');
            $table->dropIndex('idx_wa_logs_tanggal');
        });

        Schema::table('wa_scheduled_reports', function (Blueprint $table) {
            $table->dropIndex('idx_wa_reports_aktif_jam');
        });

        Schema::table('pengumuman', function (Blueprint $table) {
            $table->dropIndex('idx_pengumuman_aktif_marquee');
            $table->dropIndex('idx_pengumuman_dates');
        });
    }
};
