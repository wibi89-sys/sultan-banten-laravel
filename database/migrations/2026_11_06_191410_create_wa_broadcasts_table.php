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
        if (! Schema::hasTable('wa_broadcasts')) {
            Schema::create('wa_broadcasts', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('pengumuman_id')->nullable()->index();
                $table->string('judul');
                $table->string('kategori', 50)->default('Informasi');
                $table->text('pesan');
                $table->string('target_type', 30)->default('all');
                $table->json('target_roles')->nullable();
                $table->json('target_upt_ids')->nullable();
                $table->boolean('kirim_ke_grup')->default(false);
                $table->integer('total_target')->default(0);
                $table->integer('total_sent')->default(0);
                $table->integer('total_failed')->default(0);
                $table->string('status', 30)->default('completed');
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->dateTime('sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wa_broadcast_logs')) {
            Schema::create('wa_broadcast_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('broadcast_id')->constrained('wa_broadcasts')->cascadeOnDelete();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('nama_penerima');
                $table->string('no_wa', 30);
                $table->string('role', 50)->nullable();
                $table->string('nama_upt')->nullable();
                $table->string('status', 30)->default('sent');
                $table->text('response_message')->nullable();
                $table->dateTime('sent_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_broadcast_logs');
        Schema::dropIfExists('wa_broadcasts');
    }
};
