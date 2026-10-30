<?php

namespace App\Console\Commands;

use App\Models\MutasiNarapidana;
use App\Models\MutasiNarapidanaTujuan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ClearMutasiDummyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mutasi:clear-dummy {--force : Eksekusi langsung tanpa konfirmasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menghapus seluruh data usulan mutasi narapidana dan membersihkan berkas PDF dummy';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Apakah Anda yakin ingin menghapus seluruh data usulan mutasi narapidana dan berkas uploadnya?')) {
            $this->info('Operasi dibatalkan.');

            return self::SUCCESS;
        }

        $this->info('Membersihkan data mutasi narapidana dari database...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        MutasiNarapidanaTujuan::truncate();
        MutasiNarapidana::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Membersihkan berkas di public/uploads/mutasi/ ...');
        $uploadDir = public_path('uploads/mutasi');
        if (File::exists($uploadDir)) {
            $files = File::files($uploadDir);
            $deletedCount = 0;
            foreach ($files as $file) {
                if ($file->getFilename() !== '.gitkeep') {
                    File::delete($file->getRealPath());
                    $deletedCount++;
                }
            }
            $this->info("Berhasil menghapus {$deletedCount} berkas.");
        } else {
            File::makeDirectory($uploadDir, 0755, true);
        }

        // Pastikan .gitkeep tetap ada
        if (! File::exists($uploadDir.'/.gitkeep')) {
            File::put($uploadDir.'/.gitkeep', '');
        }

        $this->info('Data usulan mutasi narapidana dan berkas dummy berhasil dibersihkan.');

        return self::SUCCESS;
    }
}
