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
        if (Schema::hasTable('tpp_naskah_kanwil')) {
            Schema::table('tpp_naskah_kanwil', function (Blueprint $table) {
                if (! Schema::hasColumn('tpp_naskah_kanwil', 'is_locked')) {
                    $table->boolean('is_locked')->default(false)->after('status');
                }
                if (! Schema::hasColumn('tpp_naskah_kanwil', 'locked_at')) {
                    $table->timestamp('locked_at')->nullable()->after('is_locked');
                }
                if (! Schema::hasColumn('tpp_naskah_kanwil', 'locked_by_id')) {
                    $table->foreignId('locked_by_id')->nullable()->after('locked_at')->constrained('users')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tpp_naskah_kanwil', function (Blueprint $table) {
            $table->dropForeign(['locked_by_id']);
            $table->dropColumn(['is_locked', 'locked_at', 'locked_by_id']);
        });
    }
};
