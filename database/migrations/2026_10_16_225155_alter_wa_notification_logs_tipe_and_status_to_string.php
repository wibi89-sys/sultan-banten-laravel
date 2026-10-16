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
        if (Schema::hasTable('wa_notification_logs')) {
            Schema::table('wa_notification_logs', function (Blueprint $table) {
                $table->string('tipe', 50)->change();
                $table->string('status', 50)->default('success')->change();
            });
        }

        if (Schema::hasTable('wa_scheduled_report_logs')) {
            Schema::table('wa_scheduled_report_logs', function (Blueprint $table) {
                $table->string('status', 50)->default('success')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('wa_notification_logs')) {
            Schema::table('wa_notification_logs', function (Blueprint $table) {
                $table->enum('tipe', ['reminder_pagi', 'reminder_siang', 'test'])->change();
                $table->enum('status', ['success', 'failed'])->default('success')->change();
            });
        }

        if (Schema::hasTable('wa_scheduled_report_logs')) {
            Schema::table('wa_scheduled_report_logs', function (Blueprint $table) {
                $table->enum('status', ['success', 'failed'])->default('success')->change();
            });
        }
    }
};
