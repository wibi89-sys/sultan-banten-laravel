<?php

namespace Database\Seeders;

use App\Models\MutasiNarapidana;
use App\Models\Upt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class MutasiNarapidanaDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pastikan folder uploads/mutasi ada
        $uploadDir = public_path('uploads/mutasi');
        if (! File::exists($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        // 2. Siapkan User Admin dan UPT
        $userAdmin = User::where('role', 'admin')->first() ?? User::first();

        // Aktifkan akses mutasi untuk semua admin dan operator UPT agar bisa langsung diuji
        User::whereIn('role', ['admin', 'operator'])->update(['akses_mutasi' => true]);

        // Dapatkan beberapa UPT di wilayah Banten
        $uptLapasI_Tangerang = Upt::where('nama_upt', 'like', '%KELAS I TANGERANG%')->first() ?? Upt::find(1);
        $uptPemuda_Tangerang = Upt::where('nama_upt', 'like', '%PEMUDA%')->first() ?? Upt::find(3);
        $uptLapas_Serang = Upt::where('nama_upt', 'like', '%KELAS IIA SERANG%')->first() ?? Upt::find(6);
        $uptCiangir = Upt::where('nama_upt', 'like', '%CIANGIR%')->first() ?? Upt::find(9);
        $uptCilegon = Upt::where('nama_upt', 'like', '%CILEGON%')->first() ?? Upt::find(10);
        $uptRutan_Tangerang = Upt::where('nama_upt', 'like', '%RUTAN%TANGERANG%')->first() ?? Upt::find(11);
        $uptRutan_Serang = Upt::where('nama_upt', 'like', '%RUTAN%SERANG%')->first() ?? Upt::find(12);

        // Cari creator user untuk masing-masing UPT jika ada
        $userRutanSerang = User::where('upt_id', $uptRutan_Serang?->id)->first() ?? $userAdmin;
        $userPemuda = User::where('upt_id', $uptPemuda_Tangerang?->id)->first() ?? $userAdmin;
        $userCilegon = User::where('upt_id', $uptCilegon?->id)->first() ?? $userAdmin;
        $userRutanTangerang = User::where('upt_id', $uptRutan_Tangerang?->id)->first() ?? $userAdmin;
        $userLapasSerang = User::where('upt_id', $uptLapas_Serang?->id)->first() ?? $userAdmin;

        // 3. Generate berkas dummy PDF nyata yang valid
        $this->generateDummyPdf(
            $uploadDir.'/surat_usulan_serang_101.pdf',
            'SURAT USULAN MUTASI NARAPIDANA',
            'No: W12.PAS.PAS.08-PK.01.04-101/2026 - Rutan Kelas IIB Serang'
        );
        $this->generateDummyPdf(
            $uploadDir.'/asesmen_serang_101.pdf',
            'HASIL ASESMEN PENEMPATAN NARAPIDANA',
            'Penilaian Kebutuhan & Risiko Pembinaan - Rutan Serang'
        );

        $this->generateDummyPdf(
            $uploadDir.'/surat_usulan_pemuda_205.pdf',
            'SURAT USULAN MUTASI KATEGORI KEAMANAN',
            'No: W12.PAS.PAS.02-PK.01.04-205/2026 - Lapas Pemuda Tangerang'
        );
        $this->generateDummyPdf(
            $uploadDir.'/bap_pemuda_205.pdf',
            'BERITA ACARA PEMERIKSAAN (BAP) PELANGGARAN TATA TERTIB',
            'Pemeriksaan Gangguan Kamtib & Pemisahan Jaringan - Lapas Pemuda'
        );
        $this->generateDummyPdf(
            $uploadDir.'/reg_f_pemuda_205.pdf',
            'BUKU CATATAN PELANGGARAN DISIPLIN (REGISTER F)',
            'Register F WBP Risiko Tinggi - Lapas Pemuda Tangerang'
        );

        $this->generateDummyPdf(
            $uploadDir.'/surat_usulan_cilegon_315.pdf',
            'SURAT USULAN MUTASI OVERKAPASITAS',
            'No: W12.PAS.PAS.06-PK.01.04-315/2026 - Lapas Kelas IIA Cilegon'
        );
        $this->generateDummyPdf(
            $uploadDir.'/persetujuan_kanwil_cilegon_315.pdf',
            'SURAT PERSETUJUAN KANWIL KEMENKUMHAM BANTEN',
            'No: W12.PAS.PK.01.04-452 Tahun 2026 - Penunjukan Lapas Kelas I Tangerang'
        );

        $this->generateDummyPdf(
            $uploadDir.'/surat_usulan_rutira_411.pdf',
            'SURAT USULAN PEMINDAHAN NARAPIDANA ASIMILASI',
            'No: W12.PAS.PAS.03-PK.01.04-411/2026 - Rutan Kelas I Tangerang'
        );
        $this->generateDummyPdf(
            $uploadDir.'/persetujuan_kanwil_rutira_411.pdf',
            'SURAT PERSETUJUAN KANWIL - PENUNJUKAN LAPAS TERBUKA CIANGIR',
            'No: W12.PAS.PK.01.04-512 Tahun 2026 - Kuota 15 WBP'
        );
        $this->generateDummyPdf(
            $uploadDir.'/laporan_pemindahan_rutira_411.pdf',
            'LAPORAN PELAKSANAAN PEMINDAHAN (UPT PENGIRIM)',
            'Berita Acara Keberangkatan & Pengawalan - Rutan Kelas I Tangerang'
        );

        $this->generateDummyPdf(
            $uploadDir.'/surat_usulan_lasser_088.pdf',
            'SURAT USULAN MUTASI REVITALISASI PEMBINAAN',
            'No: W12.PAS.PAS.01-PK.01.04-088/2026 - Lapas Kelas IIA Serang'
        );
        $this->generateDummyPdf(
            $uploadDir.'/persetujuan_kanwil_lasser_088.pdf',
            'SURAT PERSETUJUAN KANWIL KEMENKUMHAM BANTEN',
            'No: W12.PAS.PK.01.04-320 Tahun 2026 - Lapas Serang ke Lapas Cilegon'
        );
        $this->generateDummyPdf(
            $uploadDir.'/laporan_pemindahan_lasser_088.pdf',
            'LAPORAN PEMINDAHAN RESMI (UPT PENGIRIM: LAPAS SERANG)',
            'Pelaksanaan Pengawalan Pemindahan 20 WBP ke Lapas Cilegon'
        );
        $this->generateDummyPdf(
            $uploadDir.'/laporan_penerimaan_lascil_088.pdf',
            'LAPORAN PENERIMAAN RESMI (UPT PENERIMA: LAPAS CILEGON)',
            'Pemeriksaan Fisik, Berkas & Penempatan Kamar Hunian Lapas Cilegon'
        );
        $this->generateDummyPdf(
            $uploadDir.'/bast_lasser_lascil_088.pdf',
            'BERITA ACARA SERAH TERIMA (BAST) NARAPIDANA',
            'No: BAST.04/W12.PAS/PK.01.04/2026 - Lapas Serang & Lapas Cilegon'
        );

        // 4. Simpan Record Usulan Dummy Mewakili 5 Skenario Berbeda

        // Usulan 1: Baru Diajukan UPT (Tahap 1 - Menunggu Telaah Kanwil)
        MutasiNarapidana::updateOrCreate(
            ['nomor_surat' => 'W12.PAS.PAS.08-PK.01.04-101/2026'],
            [
                'tanggal_surat' => Carbon::now()->subDays(2),
                'upt_asal_id' => $uptRutan_Serang?->id ?? 12,
                'kategori_mutasi' => 'Pembinaan',
                'keterangan' => 'Usulan pemindahan narapidana yang telah memenuhi syarat administratif dan substantif untuk melanjutkan program pembinaan lanjutan di Lapas.',
                'jumlah_narkotika' => 12,
                'jumlah_pidum' => 8,
                'jumlah_korupsi' => 0,
                'total_usulan' => 20,
                'file_surat_usulan' => 'uploads/mutasi/surat_usulan_serang_101.pdf',
                'file_asesmen' => 'uploads/mutasi/asesmen_serang_101.pdf',
                'status' => 'diajukan',
                'created_by' => $userRutanSerang->id,
            ]
        );

        // Usulan 2: Perlu Perbaikan Berkas (Tahap 1 - Ada Catatan Kanwil)
        MutasiNarapidana::updateOrCreate(
            ['nomor_surat' => 'W12.PAS.PAS.02-PK.01.04-205/2026'],
            [
                'tanggal_surat' => Carbon::now()->subDays(4),
                'upt_asal_id' => $uptPemuda_Tangerang?->id ?? 3,
                'kategori_mutasi' => 'Keamanan',
                'keterangan' => 'Pemindahan narapidana risiko tinggi (High-Risk) yang terindikasi melakukan pelanggaran tata tertib dan pemisahan jaringan kamtib di dalam blok.',
                'jumlah_narkotika' => 8,
                'jumlah_pidum' => 2,
                'jumlah_korupsi' => 0,
                'total_usulan' => 10,
                'file_surat_usulan' => 'uploads/mutasi/surat_usulan_pemuda_205.pdf',
                'file_bap' => 'uploads/mutasi/bap_pemuda_205.pdf',
                'file_reg_f' => 'uploads/mutasi/reg_f_pemuda_205.pdf',
                'status' => 'perlu_perbaikan',
                'catatan_kanwil' => 'Berkas BAP dan hasil asesmen risiko belum dibubuhi tanda tangan Kepala KPR dan Kalapas. Mohon lengkapi tanda tangan dan unggah ulang berkas persyaratan.',
                'diverifikasi_oleh' => $userAdmin->id,
                'tanggal_verifikasi' => Carbon::now()->subDays(1),
                'created_by' => $userPemuda->id,
            ]
        );

        // Usulan 3: Telah Disetujui Kanwil (Tahap 2 - Menunggu UPT Mengunggah Laporan Pemindahan/Penerimaan)
        MutasiNarapidana::updateOrCreate(
            ['nomor_surat' => 'W12.PAS.PAS.06-PK.01.04-315/2026'],
            [
                'tanggal_surat' => Carbon::now()->subDays(6),
                'upt_asal_id' => $uptCilegon?->id ?? 10,
                'kategori_mutasi' => 'Overkapasitas',
                'keterangan' => 'Pengurangan beban hunian Lapas Cilegon yang saat ini mencapai 250% overkapasitas untuk dialihkan ke Lapas yang memiliki kapasitas hunian memadai.',
                'jumlah_narkotika' => 18,
                'jumlah_pidum' => 10,
                'jumlah_korupsi' => 2,
                'total_usulan' => 30,
                'file_surat_usulan' => 'uploads/mutasi/surat_usulan_cilegon_315.pdf',
                'status' => 'disetujui',
                'upt_tujuan_id' => $uptLapasI_Tangerang?->id ?? 1,
                'nomor_surat_kanwil' => 'W12.PAS.PK.01.04-452 Tahun 2026',
                'tanggal_surat_kanwil' => Carbon::now()->subDays(2),
                'file_persetujuan_kanwil' => 'uploads/mutasi/persetujuan_kanwil_cilegon_315.pdf',
                'catatan_kanwil' => 'Disetujui untuk dipindahkan ke Lapas Kelas I Tangerang sebanyak 25 orang. Lakukan pengawalan ketat sesuai SOP dan laporkan pelaksanaannya.',
                'jumlah_disetujui_narkotika' => 15,
                'jumlah_disetujui_pidum' => 8,
                'jumlah_disetujui_korupsi' => 2,
                'total_disetujui' => 25,
                'diverifikasi_oleh' => $userAdmin->id,
                'tanggal_verifikasi' => Carbon::now()->subDays(2),
                'created_by' => $userCilegon->id,
            ]
        );

        // Usulan 4: Pemindahan Sedang Berjalan (Tahap 3 - UPT Pengusul Sudah Mengunggah Laporan Pemindahan)
        MutasiNarapidana::updateOrCreate(
            ['nomor_surat' => 'W12.PAS.PAS.03-PK.01.04-411/2026'],
            [
                'tanggal_surat' => Carbon::now()->subDays(8),
                'upt_asal_id' => $uptRutan_Tangerang?->id ?? 11,
                'kategori_mutasi' => 'Pembinaan',
                'keterangan' => 'Usulan mutasi WBP asimilasi pertanian dan pembinaan kemandirian ke Lapas Terbuka Ciangir.',
                'jumlah_narkotika' => 0,
                'jumlah_pidum' => 15,
                'jumlah_korupsi' => 0,
                'total_usulan' => 15,
                'file_surat_usulan' => 'uploads/mutasi/surat_usulan_rutira_411.pdf',
                'status' => 'disetujui',
                'upt_tujuan_id' => $uptCiangir?->id ?? 9,
                'nomor_surat_kanwil' => 'W12.PAS.PK.01.04-512 Tahun 2026',
                'tanggal_surat_kanwil' => Carbon::now()->subDays(3),
                'file_persetujuan_kanwil' => 'uploads/mutasi/persetujuan_kanwil_rutira_411.pdf',
                'catatan_kanwil' => 'Disetujui penuh 15 orang WBP tindak pidana umum untuk pembinaan di Lapas Terbuka Kelas IIB Ciangir.',
                'jumlah_disetujui_narkotika' => 0,
                'jumlah_disetujui_pidum' => 15,
                'jumlah_disetujui_korupsi' => 0,
                'total_disetujui' => 15,
                'diverifikasi_oleh' => $userAdmin->id,
                'tanggal_verifikasi' => Carbon::now()->subDays(3),
                // Laporan Pemindahan sudah terunggah oleh Rutan Tangerang:
                'file_laporan_pemindahan' => 'uploads/mutasi/laporan_pemindahan_rutira_411.pdf',
                'tanggal_pemindahan' => Carbon::now()->subDay(),
                'catatan_pemindahan' => 'Sebanyak 15 orang narapidana telah diberangkatkan dari Rutan Tangerang pukul 08.30 WIB dengan pengawalan 6 petugas staf KPR & Kamtib.',
                'created_by' => $userRutanTangerang->id,
            ]
        );

        // Usulan 5: Selesai / Tuntas Lengkap (Tahap 4 - Laporan Pemindahan, Laporan Penerimaan, dan BAST Lengkap)
        MutasiNarapidana::updateOrCreate(
            ['nomor_surat' => 'W12.PAS.PAS.01-PK.01.04-088/2026'],
            [
                'tanggal_surat' => Carbon::now()->subDays(14),
                'upt_asal_id' => $uptLapas_Serang?->id ?? 6,
                'kategori_mutasi' => 'Pembinaan',
                'keterangan' => 'Pemindahan narapidana dalam rangka revitalisasi penyelenggaraan pemasyarakatan dan pemerataan sarana pembinaan kerja.',
                'jumlah_narkotika' => 14,
                'jumlah_pidum' => 6,
                'jumlah_korupsi' => 0,
                'total_usulan' => 20,
                'file_surat_usulan' => 'uploads/mutasi/surat_usulan_lasser_088.pdf',
                'status' => 'selesai',
                'upt_tujuan_id' => $uptCilegon?->id ?? 10,
                'nomor_surat_kanwil' => 'W12.PAS.PK.01.04-320 Tahun 2026',
                'tanggal_surat_kanwil' => Carbon::now()->subDays(10),
                'file_persetujuan_kanwil' => 'uploads/mutasi/persetujuan_kanwil_lasser_088.pdf',
                'catatan_kanwil' => 'Disetujui 20 WBP untuk pembinaan industri bengkel kerja di Lapas Kelas IIA Cilegon.',
                'jumlah_disetujui_narkotika' => 14,
                'jumlah_disetujui_pidum' => 6,
                'jumlah_disetujui_korupsi' => 0,
                'total_disetujui' => 20,
                'diverifikasi_oleh' => $userAdmin->id,
                'tanggal_verifikasi' => Carbon::now()->subDays(10),
                // Laporan Pemindahan (UPT Pengusul)
                'file_laporan_pemindahan' => 'uploads/mutasi/laporan_pemindahan_lasser_088.pdf',
                'tanggal_pemindahan' => Carbon::now()->subDays(8),
                'catatan_pemindahan' => 'Pengawalan 20 narapidana menggunakan kendaraan transpas Lapas Serang berjalan aman dan kondusif.',
                // Laporan Penerimaan (UPT Penerima)
                'file_laporan_penerimaan' => 'uploads/mutasi/laporan_penerimaan_lascil_088.pdf',
                'tanggal_penerimaan' => Carbon::now()->subDays(8),
                'catatan_penerimaan' => '20 WBP tiba di Lapas Cilegon pukul 11.00 WIB. Dilakukan tes kesehatan, penggeledahan badan & barang bawaan, serta penempatan di blok orientasi.',
                // BAST Bersama
                'nomor_bast' => 'BAST.04/W12.PAS/PK.01.04/2026',
                'tanggal_bast' => Carbon::now()->subDays(8),
                'tanggal_pelaksanaan' => Carbon::now()->subDays(8),
                'file_bast' => 'uploads/mutasi/bast_lasser_lascil_088.pdf',
                'catatan_pelaksanaan' => 'Serah terima fisik 20 narapidana beserta berkas register pembinaan telah ditandatangani bersama oleh perwakilan Lapas Serang dan Lapas Cilegon.',
                'created_by' => $userLapasSerang->id,
            ]
        );
    }

    /**
     * Buat file PDF valid sederhana tanpa library pihak ketiga.
     */
    private function generateDummyPdf(string $fullPath, string $title, string $subtitle): void
    {
        if (File::exists($fullPath)) {
            return;
        }

        $safeTitle = addcslashes($title, '()\\');
        $safeSubtitle = addcslashes($subtitle, '()\\');

        $content = "%PDF-1.4\n".
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n".
            "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n".
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n".
            "4 0 obj << /Length 260 >> stream\n".
            "BT\n/F1 15 Tf\n50 780 Td\n({$safeTitle}) Tj\n".
            "/F1 11 Tf\n0 -28 Td\n({$safeSubtitle}) Tj\n".
            "/F1 10 Tf\n0 -25 Td\n(SULTAN BANTEN - Modul Persuratan Mutasi Narapidana Antar-UPT) Tj\n".
            "/F1 9 Tf\n0 -20 Td\n(Kantor Wilayah Kementerian Hukum dan HAM / Ditjenpas Banten) Tj\n".
            "/F1 9 Tf\n0 -20 Td\n(Dokumen Resmi Simulasi Dummy Terverifikasi Sistem) Tj\n".
            "ET\nendstream\nendobj\n".
            "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> endobj\n".
            "xref\n0 6\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000227 00000 n \n0000000540 00000 n \n".
            "trailer << /Size 6 /Root 1 0 R >>\nstartxref\n620\n%%EOF";

        File::put($fullPath, $content);
    }
}
