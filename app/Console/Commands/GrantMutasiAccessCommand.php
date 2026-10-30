<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class GrantMutasiAccessCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:grant-mutasi {username? : Username spesifik yang ingin diberikan akses (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memberikan hak akses modul Mutasi Narapidana ke user tertentu atau ke semua Operator UPT';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // 1. Pastikan kolom akses_mutasi ada pada tabel users
        if (! Schema::hasTable('users')) {
            $this->error('Tabel users tidak ditemukan di database.');

            return self::FAILURE;
        }

        if (! Schema::hasColumn('users', 'akses_mutasi')) {
            $this->info('Menambahkan kolom akses_mutasi pada tabel users...');
            Schema::table('users', function ($table) {
                $table->string('akses_mutasi', 10)->default('none');
            });
            $this->info('Kolom akses_mutasi berhasil ditambahkan.');
        }

        $username = $this->argument('username');

        if ($username) {
            $user = User::where('username', $username)->first();
            if (! $user) {
                $this->error("User dengan username '{$username}' tidak ditemukan.");

                return self::FAILURE;
            }

            $user->update(['akses_mutasi' => 'full']);
            $this->info("Akses Mutasi Narapidana berhasil diberikan ke user: {$user->nama_lengkap} ({$user->username}).");

            return self::SUCCESS;
        }

        $count = User::where('role', 'operator')->update(['akses_mutasi' => 'full']);
        $this->info("Berhasil mengaktifkan akses Mutasi Narapidana untuk {$count} akun Operator UPT.");

        return self::SUCCESS;
    }
}
