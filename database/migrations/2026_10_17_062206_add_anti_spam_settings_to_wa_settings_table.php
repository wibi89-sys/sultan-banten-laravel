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
            if (! Schema::hasColumn('wa_settings', 'jeda_kirim')) {
                $table->unsignedSmallInteger('jeda_kirim')->default(4)->after('jam_reminder_siang');
            }
            if (! Schema::hasColumn('wa_settings', 'daftar_salam')) {
                $table->text('daftar_salam')->nullable()->after('jeda_kirim');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_settings', function (Blueprint $table) {
            if (Schema::hasColumn('wa_settings', 'jeda_kirim')) {
                $table->dropColumn('jeda_kirim');
            }
            if (Schema::hasColumn('wa_settings', 'daftar_salam')) {
                $table->dropColumn('daftar_salam');
            }
        });
    }
};
