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
                $table->string('kategori_mutasi', 100)->default('Pembinaan')->change();
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
                $table->enum('kategori_mutasi', ['Pembinaan', 'Keamanan', 'Overkapasitas', 'Lainnya'])->default('Pembinaan')->change();
            });
        }
    }
};
