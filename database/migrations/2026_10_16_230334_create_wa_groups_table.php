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
        Schema::create('wa_groups', function (Blueprint $table) {
            $table->id();
            $table->string('nama_grup', 100);
            $table->string('group_id', 100);
            $table->string('keterangan', 255)->nullable();
            $table->boolean('aktif')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Seed existing group from wa_settings if available
        try {
            $setting = DB::table('wa_settings')->first();
            if ($setting && ! empty($setting->group_id)) {
                DB::table('wa_groups')->insert([
                    'nama_grup' => 'Grup Utama (Kanwil Banten)',
                    'group_id' => $setting->group_id,
                    'keterangan' => 'Grup utama dari pengaturan sebelumnya',
                    'aktif' => true,
                    'is_default' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (Throwable $e) {
            // Ignore if wa_settings doesn't exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_groups');
    }
};
