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
        if (Schema::hasTable('wa_settings')) {
            Schema::table('wa_settings', function (Blueprint $table) {
                if (! Schema::hasColumn('wa_settings', 'template_pengingat_mutasi_pemindahan')) {
                    $table->text('template_pengingat_mutasi_pemindahan')->nullable()->after('template_pengingat_mutasi');
                }
                if (! Schema::hasColumn('wa_settings', 'template_pengingat_mutasi_penerimaan')) {
                    $table->text('template_pengingat_mutasi_penerimaan')->nullable()->after('template_pengingat_mutasi_pemindahan');
                }
                if (! Schema::hasColumn('wa_settings', 'template_pengingat_mutasi_bast')) {
                    $table->text('template_pengingat_mutasi_bast')->nullable()->after('template_pengingat_mutasi_penerimaan');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('wa_settings')) {
            Schema::table('wa_settings', function (Blueprint $table) {
                $columns = ['template_pengingat_mutasi_pemindahan', 'template_pengingat_mutasi_penerimaan', 'template_pengingat_mutasi_bast'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('wa_settings', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
