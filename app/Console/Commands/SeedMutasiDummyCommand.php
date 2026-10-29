<?php

namespace App\Console\Commands;

use Database\Seeders\MutasiNarapidanaDummySeeder;
use Illuminate\Console\Command;

class SeedMutasiDummyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mutasi:seed-dummy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengisi data dummy simulasi alur usulan mutasi narapidana lengkap dengan berkas PDF';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai seeding data dummy usulan mutasi narapidana...');

        $seeder = new MutasiNarapidanaDummySeeder;
        $seeder->run();

        $this->info('Berhasil menginjeksi 5 skenario dummy mutasi narapidana (Diajukan, Perlu Perbaikan, Disetujui, Dalam Proses Pemindahan, dan Selesai BAST).');
        $this->info('Seluruh berkas PDF dummy telah dibuat di folder public/uploads/mutasi/.');

        return self::SUCCESS;
    }
}
