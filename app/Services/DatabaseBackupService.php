<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseBackupService
{
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');

        if (! File::exists($this->backupDir)) {
            File::makeDirectory($this->backupDir, 0755, true);

            // Lindungi folder dari eksekusi langsung via web server
            File::put($this->backupDir.'/.gitignore', "*\n!.gitignore\n");
            File::put($this->backupDir.'/.htaccess', "Deny from all\n");
        }
    }

    /**
     * Dapatkan path direktori penyimpanan backup.
     */
    public function getBackupDirectory(): string
    {
        return $this->backupDir;
    }

    /**
     * Ambil informasi statistik database saat ini.
     *
     * @return array<string, mixed>
     */
    public function getDatabaseStats(): array
    {
        $dbName = DB::connection()->getDatabaseName();
        $driver = DB::connection()->getDriverName();

        $pdo = DB::connection()->getPdo();
        $serverVersion = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);

        $tablesResult = DB::select('
            SELECT
                table_name as `name`,
                round(((data_length + index_length) / 1024 / 1024), 2) as `size_mb`,
                table_rows as `rows`
            FROM information_schema.TABLES
            WHERE table_schema = ?
            ORDER BY (data_length + index_length) DESC
        ', [$dbName]);

        $totalTables = count($tablesResult);
        $totalSizeMb = 0.0;
        $totalRows = 0;

        foreach ($tablesResult as $t) {
            $totalSizeMb += (float) $t->size_mb;
            $totalRows += (int) $t->rows;
        }

        return [
            'database_name' => $dbName,
            'driver' => strtoupper($driver),
            'server_version' => $serverVersion,
            'total_tables' => $totalTables,
            'total_rows' => $totalRows,
            'total_size_mb' => round($totalSizeMb, 2),
            'tables' => array_map(fn ($item) => (array) $item, $tablesResult),
        ];
    }

    /**
     * Dapatkan daftar file backup yang tersimpan di server.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getBackupFiles(): array
    {
        if (! File::exists($this->backupDir)) {
            return [];
        }

        $files = File::files($this->backupDir);
        $list = [];

        foreach ($files as $file) {
            $filename = $file->getFilename();

            if ($filename === '.gitignore' || $filename === '.htaccess') {
                continue;
            }

            if (! preg_match('/\.(sql|sql\.gz)$/i', $filename)) {
                continue;
            }

            $sizeBytes = $file->getSize();
            $isCompressed = str_ends_with(strtolower($filename), '.gz');

            $list[] = [
                'filename' => $filename,
                'path' => $file->getPathname(),
                'size_bytes' => $sizeBytes,
                'size_formatted' => $this->formatBytes($sizeBytes),
                'is_compressed' => $isCompressed,
                'created_at' => date('d M Y, H:i:s', $file->getMTime()),
                'timestamp' => $file->getMTime(),
            ];
        }

        // Urutkan dari file paling baru dibuat
        usort($list, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $list;
    }

    /**
     * Buat file backup database lengkap ke disk storage.
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function generateBackupFile(?string $customFilename = null, bool $compress = true): array
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        $startTime = microtime(true);
        $timestamp = date('Y-m-d_His');
        $ext = $compress ? 'sql.gz' : 'sql';

        $filename = $customFilename ?: 'sultan_banten_backup_'.$timestamp.'.'.$ext;

        // Sanitasi nama file
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
        if (! str_ends_with($filename, '.'.$ext)) {
            $filename .= '.'.$ext;
        }

        $targetPath = $this->backupDir.DIRECTORY_SEPARATOR.$filename;

        // Buka file handle (gzip atau fopen biasa)
        $handle = $compress ? @gzopen($targetPath, 'w9') : @fopen($targetPath, 'w');

        if (! $handle) {
            throw new Exception("Gagal membuka file tujuan cadangan: {$targetPath}");
        }

        $write = function (string $text) use ($handle, $compress) {
            if ($compress) {
                gzwrite($handle, $text);
            } else {
                fwrite($handle, $text);
            }
        };

        try {
            $this->writeDumpContent($write);
        } finally {
            if ($compress) {
                gzclose($handle);
            } else {
                fclose($handle);
            }
        }

        $timeTaken = round(microtime(true) - $startTime, 2);
        $sizeBytes = file_exists($targetPath) ? (int) filesize($targetPath) : 0;

        return [
            'filename' => $filename,
            'path' => $targetPath,
            'size_bytes' => $sizeBytes,
            'size_formatted' => $this->formatBytes($sizeBytes),
            'is_compressed' => $compress,
            'time_taken_seconds' => $timeTaken,
        ];
    }

    /**
     * Hapus file backup dari server.
     */
    public function deleteBackupFile(string $filename): bool
    {
        // Cegah path traversal
        if (! preg_match('/^[a-zA-Z0-9_\-]+\.(sql|sql\.gz)$/i', $filename)) {
            return false;
        }

        $filePath = $this->backupDir.DIRECTORY_SEPARATOR.$filename;

        if (File::exists($filePath)) {
            return File::delete($filePath);
        }

        return false;
    }

    /**
     * Dapatkan path lengkap file backup yang valid dan aman.
     */
    public function getValidBackupPath(string $filename): ?string
    {
        if (! preg_match('/^[a-zA-Z0-9_\-]+\.(sql|sql\.gz)$/i', $filename)) {
            return null;
        }

        $filePath = $this->backupDir.DIRECTORY_SEPARATOR.$filename;

        return File::exists($filePath) ? $filePath : null;
    }

    /**
     * Urutan tabel induk (parent tables) yang harus dibuat lebih dulu
     * sebelum tabel anak (child tables) yang memiliki Foreign Key.
     *
     * @var array<int, string>
     */
    protected array $parentTablePriority = [
        'migrations',
        'upt',
        'users',
        'instansi',
        'pengumuman',
        'sidang_tpp',
        'mutasi_narapidana',
        'wa_settings',
        'wa_groups',
        'wa_pimpinan',
        'wa_broadcasts',
        'wa_scheduled_reports',
        'ai_settings',
        'master_pegawai_kanwil',
    ];

    /**
     * Pulihkan (Import / Restore) database dari file .sql atau .sql.gz.
     * Otomatis menghapus tabel lama terlebih dahulu di bawah FOREIGN_KEY_CHECKS=0
     * agar tidak terjadi konflik tipe kolom Foreign Key (MySQL Error 3780)
     * antara server produksi dan server lokal.
     *
     * @return array<string, mixed>
     *
     * @throws Exception
     */
    public function restoreFromFile(string $filePath): array
    {
        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        if (! File::exists($filePath) || ! is_readable($filePath)) {
            throw new Exception('File cadangan tidak ditemukan atau tidak dapat dibaca.');
        }

        $startTime = microtime(true);

        // Deteksi apakah file terkompresi GZIP berdasarkan magic bytes (\x1f\x8b) atau ekstensi .gz
        $magic = '';
        $probe = @fopen($filePath, 'rb');
        if ($probe) {
            $magic = (string) fread($probe, 2);
            fclose($probe);
        }
        $isGzip = ($magic === "\x1f\x8b") || str_ends_with(strtolower($filePath), '.gz');

        // Pra-pindai file untuk memvalidasi isi SQL dan mendata tabel yang akan dibuat ulang
        $scanInfo = $this->scanSqlDumpFile($filePath, $isGzip);
        if (! $scanInfo['is_valid']) {
            throw new Exception('File cadangan tidak berisi perintah SQL (CREATE TABLE / INSERT INTO) yang valid.');
        }

        $handle = $isGzip ? @gzopen($filePath, 'rb') : @fopen($filePath, 'rb');
        if (! $handle) {
            throw new Exception('Gagal membuka aliran file cadangan untuk pemulihan.');
        }

        $pdo = DB::connection()->getPdo();
        $executedStatements = 0;
        $tablesCreated = 0;

        try {
            $pdo->exec('SET FOREIGN_KEY_CHECKS = 0;');
            $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';");

            // Hapus terlebih dahulu seluruh tabel yang akan dibuat ulang oleh file dump ini
            // di bagian paling awal (sebelum CREATE TABLE pertama dieksekusi).
            // Ini mencegah MySQL 8.0/8.4 Error 3780 (inkompatibilitas signed vs unsigned Foreign Key)
            // ketika tabel anak (misal `data_integrasi`) dieksekusi lebih dulu daripada tabel induk (`upt`).
            foreach ($scanInfo['tables_to_create'] as $tableName) {
                $safeTable = str_replace('`', '``', $tableName);
                $pdo->exec("DROP TABLE IF EXISTS `{$safeTable}`;");
            }

            $statement = '';
            $inString = false;

            while (! ($isGzip ? gzeof($handle) : feof($handle))) {
                $line = $isGzip ? gzgets($handle, 65536) : fgets($handle, 65536);
                if ($line === false) {
                    break;
                }

                // Hapus UTF-8 BOM pada baris pertama jika ada
                if ($executedStatements === 0 && $statement === '') {
                    $line = preg_replace('/^\xEF\xBB\xBF/', '', $line) ?? $line;
                }

                if (! $inString) {
                    $trimmed = ltrim($line);
                    if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                        continue;
                    }
                }

                $statement .= $line;
                $this->updateSqlStringState($line, $inString);

                if (! $inString && str_ends_with(rtrim($line), ';')) {
                    $sqlToRun = trim($statement);
                    $statement = '';

                    if ($sqlToRun !== '' && $sqlToRun !== ';') {
                        // Normalisasi collation MySQL 8.0+ / MariaDB 11+ agar kompatibel lintas versi
                        if (stripos($sqlToRun, 'CREATE TABLE') === 0) {
                            $sqlToRun = str_ireplace(
                                ['utf8mb4_0900_ai_ci', 'utf8mb4_uca1400_ai_ci'],
                                'utf8mb4_general_ci',
                                $sqlToRun
                            );
                            $tablesCreated++;
                        }

                        $pdo->exec($sqlToRun);
                        $executedStatements++;
                    }
                }
            }
        } finally {
            if ($isGzip) {
                @gzclose($handle);
            } else {
                @fclose($handle);
            }

            try {
                $pdo->exec('SET FOREIGN_KEY_CHECKS = 1;');
            } catch (Exception) {
                // Abaikan jika koneksi terputus
            }
        }

        // Jalankan migrasi secara aman apabila di kode lokal terdapat tabel/kolom baru
        // yang belum ada di file backup server lama
        try {
            Artisan::call('migrate', ['--force' => true]);
        } catch (Exception) {
            // Abaikan jika migrasi sudah sinkron
        }

        $timeTaken = round(microtime(true) - $startTime, 2);

        return [
            'tables_restored' => $tablesCreated,
            'statements_executed' => $executedStatements,
            'time_taken_seconds' => $timeTaken,
        ];
    }

    /**
     * Pindai cepat file SQL (.sql / .sql.gz) untuk memastikan file valid dan
     * mengumpulkan daftar nama tabel yang memiliki perintah CREATE TABLE.
     *
     * @return array{is_valid: bool, tables_to_create: array<int, string>}
     */
    protected function scanSqlDumpFile(string $filePath, bool $isGzip): array
    {
        $handle = $isGzip ? @gzopen($filePath, 'rb') : @fopen($filePath, 'rb');
        if (! $handle) {
            return ['is_valid' => false, 'tables_to_create' => []];
        }

        $hasValidSql = false;
        $tables = [];

        try {
            while (! ($isGzip ? gzeof($handle) : feof($handle))) {
                $line = $isGzip ? gzgets($handle, 65536) : fgets($handle, 65536);
                if ($line === false) {
                    break;
                }

                if (preg_match('/^\s*CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i', $line, $matches)) {
                    $hasValidSql = true;
                    $tables[] = $matches[1];
                } elseif (! $hasValidSql && preg_match('/^\s*INSERT\s+INTO\s+`?([a-zA-Z0-9_]+)`?/i', $line)) {
                    $hasValidSql = true;
                }
            }
        } finally {
            if ($isGzip) {
                @gzclose($handle);
            } else {
                @fclose($handle);
            }
        }

        return [
            'is_valid' => $hasValidSql,
            'tables_to_create' => array_values(array_unique($tables)),
        ];
    }

    /**
     * Lacak apakah posisi parser saat ini masih berada di dalam literal string SQL ('...').
     */
    protected function updateSqlStringState(string $line, bool &$inString): void
    {
        $len = strlen($line);
        for ($i = 0; $i < $len; $i++) {
            $ch = $line[$i];
            if ($inString && $ch === '\\') {
                $i++; // Lewati karakter yang di-escape dengan backslash

                continue;
            }
            if ($ch === "'") {
                $inString = ! $inString;
            }
        }
    }

    /**
     * Urutkan daftar tabel agar tabel induk (parent tables) selalu dibuat lebih dulu.
     *
     * @param  array<int, string>  $tables
     * @return array<int, string>
     */
    protected function sortTablesByDependency(array $tables): array
    {
        $priorityMap = array_flip($this->parentTablePriority);

        usort($tables, function (string $a, string $b) use ($priorityMap): int {
            $rankA = $priorityMap[$a] ?? 999;
            $rankB = $priorityMap[$b] ?? 999;

            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            return strcmp($a, $b);
        });

        return $tables;
    }

    /**
     * Eksekusi penulisan struktur dan data seluruh tabel database.
     *
     * @param  callable(string): void  $write
     */
    protected function writeDumpContent(callable $write): void
    {
        $dbName = DB::connection()->getDatabaseName();
        $pdo = DB::connection()->getPdo();
        $now = date('Y-m-d H:i:s');

        // Header SQL
        $write("-- ========================================================\n");
        $write("-- SIMPAS Banten - Backup Database Otomatis\n");
        $write("-- Tanggal & Jam Backup : {$now}\n");
        $write('-- Host Server          : '.config('database.connections.mysql.host', 'localhost')."\n");
        $write("-- Nama Database        : {$dbName}\n");
        $write("-- Engine Driver        : MySQL / MariaDB (PHP Native PDO Dumper)\n");
        $write("-- ========================================================\n\n");

        $write("SET FOREIGN_KEY_CHECKS = 0;\n");
        $write("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n");
        $write("SET time_zone = '+00:00';\n");
        $write("/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n");
        $write("/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n");
        $write("/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n");
        $write("/*!40101 SET NAMES utf8mb4 */;\n\n");

        // Ambil daftar semua tabel dasar (bukan view)
        $tablesRaw = DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
        $tables = [];

        foreach ($tablesRaw as $row) {
            $vals = array_values((array) $row);
            if (! empty($vals[0])) {
                $tables[] = $vals[0];
            }
        }

        // Urutkan agar tabel induk (upt, users, dll.) berada di urutan paling atas
        $tables = $this->sortTablesByDependency($tables);

        // Hapus seluruh tabel di bagian paling awal (urutan terbalik: child -> parent)
        // untuk mencegah MySQL 8.0/8.4 Error 3780 saat diimpor ke database yang sudah berisi tabel
        if (! empty($tables)) {
            $write("-- --------------------------------------------------------\n");
            $write("-- Pembersihan Awal Seluruh Tabel (Mencegah Konflik Foreign Key)\n");
            $write("-- --------------------------------------------------------\n");
            foreach (array_reverse($tables) as $table) {
                $write("DROP TABLE IF EXISTS `{$table}`;\n");
            }
            $write("\n");
        }

        foreach ($tables as $table) {
            $write("-- --------------------------------------------------------\n");
            $write("-- Struktur Tabel `{$table}`\n");
            $write("-- --------------------------------------------------------\n");
            $write("DROP TABLE IF EXISTS `{$table}`;\n");

            // Ambil query pembuatan tabel
            $createRows = DB::select("SHOW CREATE TABLE `{$table}`");
            if (! empty($createRows)) {
                $createArr = (array) $createRows[0];
                $createSql = $createArr['Create Table'] ?? '';
                if ($createSql) {
                    $write($createSql.";\n\n");
                }
            }

            // Ekspor baris data secara bertahap (chunking untuk menghemat memori)
            $write("-- --------------------------------------------------------\n");
            $write("-- Data untuk Tabel `{$table}`\n");
            $write("-- --------------------------------------------------------\n");

            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            if ($stmt) {
                $batch = [];
                $columns = null;

                while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                    if ($columns === null) {
                        $columns = array_map(fn ($col) => '`'.str_replace('`', '``', $col).'`', array_keys($row));
                    }

                    $vals = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $vals[] = 'NULL';
                        } else {
                            $vals[] = $pdo->quote((string) $val);
                        }
                    }

                    $batch[] = '('.implode(', ', $vals).')';

                    // Flush per 100 baris untuk insert multi-baris yang efisien & cepat
                    if (count($batch) >= 100) {
                        $write("INSERT INTO `{$table}` (".implode(', ', $columns).") VALUES\n".implode(",\n", $batch).";\n");
                        $batch = [];
                    }
                }

                if (! empty($batch)) {
                    $write("INSERT INTO `{$table}` (".implode(', ', $columns).") VALUES\n".implode(",\n", $batch).";\n");
                }
            }

            $write("\n");
        }

        $write("SET FOREIGN_KEY_CHECKS = 1;\n");
        $write("/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n");
        $write("/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;\n");
        $write("/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;\n");
        $write('-- Selesai dicadangkan pada: '.date('Y-m-d H:i:s')."\n");
    }

    /**
     * Format bytes ke ukuran yang mudah dibaca (KB, MB, GB).
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }
}
