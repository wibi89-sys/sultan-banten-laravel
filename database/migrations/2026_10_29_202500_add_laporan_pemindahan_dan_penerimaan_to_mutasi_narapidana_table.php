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
        if (Schema::hasTable('mutasi_narapidana')) {
            Schema::table('mutasi_narapidana', function (Blueprint $table) {
                if (! Schema::hasColumn('mutasi_narapidana', 'file_laporan_pemindahan')) {
                    $table->string('file_laporan_pemindahan', 255)->nullable();
                }
                if (! Schema::hasColumn('mutasi_narapidana', 'tanggal_pemindahan')) {
                    $table->date('tanggal_pemindahan')->nullable();
                }
                if (! Schema::hasColumn('mutasi_narapidana', 'catatan_pemindahan')) {
                    $table->text('catatan_pemindahan')->nullable();
                }

                if (! Schema::hasColumn('mutasi_narapidana', 'file_laporan_penerimaan')) {
                    $table->string('file_laporan_penerimaan', 255)->nullable();
                }
                if (! Schema::hasColumn('mutasi_narapidana', 'tanggal_penerimaan')) {
                    $table->date('tanggal_penerimaan')->nullable();
                }
                if (! Schema::hasColumn('mutasi_narapidana', 'catatan_penerimaan')) {
                    $table->text('catatan_penerimaan')->nullable();
                }

                if (! Schema::hasColumn('mutasi_narapidana', 'nomor_bast')) {
                    $table->string('nomor_bast', 100)->nullable();
                }
                if (! Schema::hasColumn('mutasi_narapidana', 'tanggal_bast')) {
                    $table->date('tanggal_bast')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mutasi_narapidana')) {
            Schema::table('mutasi_narapidana', function (Blueprint $table) {
                $columns = [
                    'file_laporan_pemindahan',
                    'tanggal_pemindahan',
                    'catatan_pemindahan',
                    'file_laporan_penerimaan',
                    'tanggal_penerimaan',
                    'catatan_penerimaan',
                    'nomor_bast',
                    'tanggal_bast',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('mutasi_narapidana', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
