<?php

namespace Database\Seeders;

use App\Models\SidangTpp;
use App\Models\Upt;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class SidangTppDummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Dapatkan UPT Rutan Pandeglang
        $upt = Upt::where('nama_upt', 'like', '%Pandeglang%')->first();
        if (! $upt) {
            $upt = Upt::firstOrCreate(
                ['nama_upt' => 'RUMAH TAHANAN NEGARA KELAS IIB PANDEGLANG'],
                [
                    'alamat' => 'Jl. Masjid Agung, Pandeglang, Banten 42211',
                    'kapasitas' => 121,
                    'latitude' => '-6.30823167',
                    'longitude' => '106.10479180',
                ]
            );
        }

        $userPandeglang = User::where('upt_id', $upt->id)->first() ?? User::where('username', 'admin')->first() ?? User::first();
        $userAdmin = User::where('role', 'admin')->first() ?? User::first();

        // 2. Siapkan Folder Upload & Berkas Fisik Nyata (PDF & JPG Dummy)
        $uploadDir = public_path('uploads/sidang_tpp');
        if (! File::exists($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $this->generateDummyPdf(
            $uploadDir.'/undangan_tpp_pandeglang_412.pdf',
            'SURAT UNDANGAN SIDANG TPP DARING',
            'Nomor: W12.PAS.PAS.13-UM.01.02-412/2026 - Rutan Pandeglang'
        );
        $this->generateDummyPdf(
            $uploadDir.'/ba_tpp_pandeglang_188.pdf',
            'BERITA ACARA SIDANG TPP RUTAN KELAS IIB PANDEGLANG',
            'Nomor: W12.PAS.PAS.13-PK.02.02-188/2026 - Tanggal 14 Oktober 2026'
        );
        $this->generateDummyPdf(
            $uploadDir.'/laporan_kanwil_pandeglang_1045.pdf',
            'LAPORAN SIDANG TPP KANTOR WILAYAH (BINTORWASDAL)',
            'Nomor: W12.PK.02.02-1045/2026 - Ka. Kanwil Ditjenpas Banten'
        );
        $this->generateDummyPdf(
            $uploadDir.'/undangan_tpp_pandeglang_425.pdf',
            'SURAT UNDANGAN SIDANG TPP RUTAN PANDEGLANG',
            'Nomor: W12.PAS.PAS.13-UM.01.02-425/2026'
        );
        $this->generateDummyPdf(
            $uploadDir.'/ba_tpp_pandeglang_195.pdf',
            'BERITA ACARA HASIL SIDANG TPP RUTAN PANDEGLANG',
            'Nomor: W12.PAS.PAS.13-PK.02.02-195/2026'
        );
        $this->generateDummyPdf(
            $uploadDir.'/undangan_tpp_pandeglang_430.pdf',
            'SURAT UNDANGAN SIDANG TPP TERJADWAL',
            'Nomor: W12.PAS.PAS.13-UM.01.02-430/2026 - Rutan Pandeglang'
        );

        $this->generateDummyJpg($uploadDir.'/foto_tpp_pandeglang_1.jpg', 'Dokumentasi Sidang TPP Rutan Pandeglang #1', 0x1E, 0x3A, 0x8A);
        $this->generateDummyJpg($uploadDir.'/foto_tpp_pandeglang_2.jpg', 'Dokumentasi Bintorwasdal Sidang Daring #2', 0x06, 0x5F, 0x46);
        $this->generateDummyJpg($uploadDir.'/foto_tpp_pandeglang_3.jpg', 'Dokumentasi Pembahasan Litmas Bapas #3', 0x0F, 0x76, 0x6E);

        // Bersihkan data dummy lama jika ada agar bersih dan reproducible
        SidangTpp::where('upt_id', $upt->id)->delete();

        // =========================================================================
        // SESI 1: STATUS SELESAI LENGKAP TTD KA. KANWIL (Verifikasi Sesuai)
        // =========================================================================
        $sidang1 = SidangTpp::create([
            'upt_id' => $upt->id,
            'nomor_undangan' => 'W12.PAS.PAS.13-UM.01.02-412/2026',
            'tanggal_surat' => '2026-10-10',
            'tanggal_sidang' => '2026-10-14',
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '11:30:00',
            'file_undangan' => 'uploads/sidang_tpp/undangan_tpp_pandeglang_412.pdf',
            'agenda_sidang' => 'Sidang TPP Usulan Pembebasan Bersyarat (PB) dan Cuti Bersyarat (CB) Tahap IV Tahun Anggaran 2026 Rutan Kelas IIB Pandeglang',
            'agenda_kategori' => 'hak_integrasi',
            'agenda_deskripsi' => 'Sidang TPP Usulan Pembebasan Bersyarat (PB) dan Cuti Bersyarat (CB) Tahap IV Tahun Anggaran 2026 Rutan Kelas IIB Pandeglang',
            'metode_sidang' => 'hybrid',
            'link_daring' => 'https://zoom.us/j/84920193810?pwd=SultanBanten2026',
            'meeting_id' => '849 2019 3810',
            'passcode' => 'TPP2026',
            'ketua_tpp' => 'Dedi Suryadi, S.H.',
            'sekretaris_tpp' => 'Rifqi Fathullah, A.Md.P.',
            'nama_pk_bapas' => 'Ahmad Fauzi, S.Sos. (PK Ahli Pertama Bapas Serang)',
            'anggota_tpp' => json_encode(['Kasubsi Pengelolaan', 'Ka. KPR', 'Dokter Rutan', 'Staf Bimkemaswat']),
            'nomor_berita_acara' => 'W12.PAS.PAS.13-PK.02.02-188/2026',
            'file_berita_acara' => 'uploads/sidang_tpp/ba_tpp_pandeglang_188.pdf',
            'file_dokumentasi' => json_encode([
                'uploads/sidang_tpp/foto_tpp_pandeglang_1.jpg',
                'uploads/sidang_tpp/foto_tpp_pandeglang_2.jpg',
            ]),
            'kanwil_hadir' => true,
            'nama_pengawas_kanwil' => 'Gunawan Wibisono, S.Sos., M.Si. (Bintorwasdal Kanwil Banten)',
            'catatan_bintorwasdal' => 'Seluruh dokumen syarat substantif dan administratif 4 orang WBP telah memenuhi kriteria Permenkumham No. 7 Tahun 2022 serta SE No: PAS-38.OT.02.02 Tahun 2026. 1 orang WBP an. TB. Maulana berkas Litmas Bapas perlu penyelarasan data penjamin dan disarankan diajukan pada periode sidang berikutnya.',
            'nomor_laporan_kanwil' => 'W12.PK.02.02-1045/2026',
            'tanggal_ttd_kakanwil' => '2026-10-16',
            'file_laporan_kakanwil' => 'uploads/sidang_tpp/laporan_kanwil_pandeglang_1045.pdf',
            'status_verifikasi_kanwil' => 'sesuai',
            'verified_by' => $userAdmin->id ?? 3,
            'verified_at' => '2026-10-16 14:20:00',
            'total_usulan' => 5,
            'total_disetujui' => 4,
            'total_ditunda' => 1,
            'total_ditolak' => 0,
            'total_setuju' => 4,
            'total_tunda' => 1,
            'total_tidak_setuju' => 0,
            'status_sidang' => 'selesai_ttd_kakanwil',
            'created_by' => $userPandeglang->id ?? 16,
        ]);

        $usulanSesi1 = [
            [
                'nama_wbp' => 'M. Ridwan bin Hasan',
                'nomor_register' => 'BI.142/2024',
                'perkara_pasal' => 'Pasal 363 KUHP (Pencurian)',
                'lama_pidana' => '2 Tahun 6 Bulan',
                'tanggal_ekspirasi' => '2027-02-15',
                'tanggal_setengah_pidana' => '2025-11-15',
                'tanggal_dua_pertiga' => '2026-04-15',
                'jenis_usulan' => 'PB',
                'skor_sppn' => 88.50,
                'status_litmas' => 'memenuhi_syarat',
                'bebas_register_f' => true,
                'keputusan' => 'setuju',
                'alasan_keterangan' => 'Memenuhi seluruh syarat administratif, substantif, dan rekomendasi Litmas Bapas Serang mendukung.',
            ],
            [
                'nama_wbp' => 'Asep Saepudin bin Sulaeman',
                'nomor_register' => 'BI.089/2024',
                'perkara_pasal' => 'Pasal 372 KUHP (Penggelapan)',
                'lama_pidana' => '1 Tahun 8 Bulan',
                'tanggal_ekspirasi' => '2026-12-10',
                'tanggal_setengah_pidana' => '2025-10-10',
                'tanggal_dua_pertiga' => '2026-03-20',
                'jenis_usulan' => 'CB',
                'skor_sppn' => 92.00,
                'status_litmas' => 'memenuhi_syarat',
                'bebas_register_f' => true,
                'keputusan' => 'setuju',
                'alasan_keterangan' => 'Penilaian SPPN sangat baik, aktif mengikuti program pembinaan kepribadian.',
            ],
            [
                'nama_wbp' => 'Hendra Gunawan bin Mansyur',
                'nomor_register' => 'BI.210/2024',
                'perkara_pasal' => 'Pasal 127 UU 35/2009 (Narkotika)',
                'lama_pidana' => '2 Tahun',
                'tanggal_ekspirasi' => '2027-01-05',
                'tanggal_setengah_pidana' => '2026-01-05',
                'tanggal_dua_pertiga' => '2026-05-05',
                'jenis_usulan' => 'PB',
                'skor_sppn' => 85.00,
                'status_litmas' => 'memenuhi_syarat',
                'bebas_register_f' => true,
                'keputusan' => 'setuju',
                'alasan_keterangan' => 'Telah menyelesaikan program rehabilitasi medis/sosial rutan dengan predikat baik.',
            ],
            [
                'nama_wbp' => 'Siti Aminah binti Karta',
                'nomor_register' => 'BI.045/2025',
                'perkara_pasal' => 'Pasal 378 KUHP (Penipuan)',
                'lama_pidana' => '1 Tahun 4 Bulan',
                'tanggal_ekspirasi' => '2026-11-28',
                'tanggal_setengah_pidana' => '2025-11-28',
                'tanggal_dua_pertiga' => '2026-03-15',
                'jenis_usulan' => 'CB',
                'skor_sppn' => 90.00,
                'status_litmas' => 'memenuhi_syarat',
                'bebas_register_f' => true,
                'keputusan' => 'setuju',
                'alasan_keterangan' => 'Berkas penjaminan dari keluarga inti lengkap, Litmas positif.',
            ],
            [
                'nama_wbp' => 'TB. Maulana bin TB. Junaedi',
                'nomor_register' => 'BI.178/2024',
                'perkara_pasal' => 'Pasal 351 KUHP (Penganiayaan)',
                'lama_pidana' => '1 Tahun 6 Bulan',
                'tanggal_ekspirasi' => '2027-03-01',
                'tanggal_setengah_pidana' => '2026-03-01',
                'tanggal_dua_pertiga' => '2026-07-01',
                'jenis_usulan' => 'CB',
                'skor_sppn' => 76.00,
                'status_litmas' => 'proses',
                'bebas_register_f' => true,
                'perlu_perhatian' => true,
                'catatan_perhatian' => 'Litmas Bapas proses perbaikan domisili penjamin',
                'keputusan' => 'tunda',
                'alasan_keterangan' => 'Penjamin keluarga inti pindah domisili, perbaikan surat keterangan domisili dari Kelurahan Pandeglang.',
            ],
        ];

        foreach ($usulanSesi1 as $item) {
            $sidang1->usulan()->create($item);
        }
        $sidang1->recalculateCounters();

        // =========================================================================
        // SESI 2: STATUS MENUNGGU VERIFIKASI KANWIL (Untuk Simulasi Menu 3 Verifikasi Dokumen & Upload Laporan Kanwil)
        // =========================================================================
        $sidang2 = SidangTpp::create([
            'upt_id' => $upt->id,
            'nomor_undangan' => 'W12.PAS.PAS.13-UM.01.02-425/2026',
            'tanggal_surat' => '2026-10-18',
            'tanggal_sidang' => '2026-10-21',
            'jam_mulai' => '13:30:00',
            'jam_selesai' => '15:45:00',
            'file_undangan' => 'uploads/sidang_tpp/undangan_tpp_pandeglang_425.pdf',
            'agenda_sidang' => 'Sidang TPP Usulan Pembebasan Bersyarat (PB), Asimilasi, dan Pemeriksaan Disiplin Register F',
            'agenda_kategori' => 'hak_integrasi',
            'agenda_deskripsi' => 'Sidang TPP Usulan Pembebasan Bersyarat (PB), Asimilasi, dan Pemeriksaan Disiplin Register F',
            'metode_sidang' => 'hybrid',
            'link_daring' => 'https://meet.google.com/qwe-rtyu-iop',
            'meeting_id' => 'qwe-rtyu-iop',
            'passcode' => 'RUTANPDG',
            'ketua_tpp' => 'Dedi Suryadi, S.H.',
            'sekretaris_tpp' => 'Rifqi Fathullah, A.Md.P.',
            'nama_pk_bapas' => 'Dina Safitri, S.H. (Bapas Serang)',
            'anggota_tpp' => json_encode(['Kasubsi Pengelolaan', 'Ka. KPR', 'Bimkemaswat']),
            'nomor_berita_acara' => 'W12.PAS.PAS.13-PK.02.02-195/2026',
            'file_berita_acara' => 'uploads/sidang_tpp/ba_tpp_pandeglang_195.pdf',
            'file_dokumentasi' => json_encode(['uploads/sidang_tpp/foto_tpp_pandeglang_3.jpg']),
            'kanwil_hadir' => false,
            'nama_pengawas_kanwil' => null,
            'catatan_bintorwasdal' => null,
            'nomor_laporan_kanwil' => null,
            'tanggal_ttd_kakanwil' => null,
            'file_laporan_kakanwil' => null,
            'status_verifikasi_kanwil' => 'menunggu_verifikasi',
            'verified_by' => null,
            'verified_at' => null,
            'total_usulan' => 4,
            'total_disetujui' => 2,
            'total_ditunda' => 1,
            'total_ditolak' => 1,
            'total_setuju' => 2,
            'total_tunda' => 1,
            'total_tidak_setuju' => 1,
            'status_sidang' => 'menunggu_verifikasi_kanwil',
            'created_by' => $userPandeglang->id ?? 16,
        ]);

        $usulanSesi2 = [
            [
                'nama_wbp' => 'Ujang Supriatna bin Jarkasih',
                'nomor_register' => 'BI.099/2024',
                'perkara_pasal' => 'Pasal 362 KUHP (Pencurian Biasa)',
                'lama_pidana' => '1 Tahun 2 Bulan',
                'tanggal_ekspirasi' => '2026-11-10',
                'tanggal_setengah_pidana' => '2025-11-10',
                'tanggal_dua_pertiga' => '2026-03-01',
                'jenis_usulan' => 'PB',
                'skor_sppn' => 89.00,
                'status_litmas' => 'memenuhi_syarat',
                'bebas_register_f' => true,
                'keputusan' => 'setuju',
                'alasan_keterangan' => 'Memenuhi syarat substantif dan administratif.',
            ],
            [
                'nama_wbp' => 'Deden Rusmana bin Komarudin',
                'nomor_register' => 'BI.131/2024',
                'perkara_pasal' => 'Pasal 114 Ayat (1) UU Narkotika',
                'lama_pidana' => '4 Tahun',
                'tanggal_ekspirasi' => '2028-06-15',
                'tanggal_setengah_pidana' => '2026-06-15',
                'tanggal_dua_pertiga' => '2027-02-15',
                'jenis_usulan' => 'PB',
                'skor_sppn' => 86.50,
                'status_litmas' => 'memenuhi_syarat',
                'bebas_register_f' => true,
                'keputusan' => 'setuju',
                'alasan_keterangan' => 'Pembayaran denda lunas, berkelakuan baik.',
            ],
            [
                'nama_wbp' => 'Suryadi bin Wahab',
                'nomor_register' => 'BI.180/2024',
                'perkara_pasal' => 'Pasal 378 KUHP (Penipuan)',
                'lama_pidana' => '2 Tahun',
                'tanggal_ekspirasi' => '2027-04-10',
                'tanggal_setengah_pidana' => '2026-04-10',
                'tanggal_dua_pertiga' => '2026-08-10',
                'jenis_usulan' => 'Asimilasi',
                'skor_sppn' => 70.00,
                'status_litmas' => 'memenuhi_syarat',
                'bebas_register_f' => true,
                'perlu_perhatian' => true,
                'catatan_perhatian' => 'Usulan Asimilasi luar tembok - perlu atensi pengawasan kanwil',
                'keputusan' => 'tunda',
                'alasan_keterangan' => 'Skor SPPN belum mencapai target asimilasi kerja luar, ditunda 1 bulan evaluasi pembinaan.',
            ],
            [
                'nama_wbp' => 'Wahyu Hidayat bin Kusnadi',
                'nomor_register' => 'BI.215/2024',
                'perkara_pasal' => 'Pasal 480 KUHP (Penadahan)',
                'lama_pidana' => '1 Tahun',
                'tanggal_ekspirasi' => '2026-10-30',
                'tanggal_setengah_pidana' => '2025-10-30',
                'tanggal_dua_pertiga' => '2026-02-28',
                'jenis_usulan' => 'Register F',
                'skor_sppn' => 52.00,
                'status_litmas' => 'tidak_memenuhi_syarat',
                'bebas_register_f' => false,
                'perlu_perhatian' => true,
                'catatan_perhatian' => 'Pelanggaran disiplin berat (catatan Register F aktif) - Perhatian Kanwil',
                'keputusan' => 'tidak_setuju',
                'alasan_keterangan' => 'Terbukti melakukan pelanggaran tata tertib rutan (buku Register F aktif), usulan hak bersyarat tidak disetujui.',
            ],
        ];

        foreach ($usulanSesi2 as $item) {
            $sidang2->usulan()->create($item);
        }
        $sidang2->recalculateCounters();

        // =========================================================================
        // SESI 3: STATUS DIJADWALKAN (Untuk Simulasi Menu 2 Input Hasil Sidang & Dokumentasi)
        // =========================================================================
        $sidang3 = SidangTpp::create([
            'upt_id' => $upt->id,
            'nomor_undangan' => 'W12.PAS.PAS.13-UM.01.02-430/2026',
            'tanggal_surat' => '2026-10-23',
            'tanggal_sidang' => '2026-10-25',
            'jam_mulai' => '09:30:00',
            'jam_selesai' => '11:45:00',
            'file_undangan' => 'uploads/sidang_tpp/undangan_tpp_pandeglang_430.pdf',
            'agenda_sidang' => 'Sidang TPP Usulan Pembebasan Bersyarat (PB) dan Cuti Bersyarat (CB) Tahap V Rutan Kelas IIB Pandeglang',
            'agenda_kategori' => 'hak_integrasi',
            'agenda_deskripsi' => 'Sidang TPP Usulan Pembebasan Bersyarat (PB) dan Cuti Bersyarat (CB) Tahap V Rutan Kelas IIB Pandeglang',
            'metode_sidang' => 'hybrid',
            'link_daring' => 'https://zoom.us/j/91238475610?pwd=PandeglangMaju2026',
            'meeting_id' => '912 3847 5610',
            'passcode' => 'PDG2026',
            'ketua_tpp' => 'Dedi Suryadi, S.H.',
            'sekretaris_tpp' => 'Rifqi Fathullah, A.Md.P.',
            'nama_pk_bapas' => 'Bambang Irawan, S.H. (Bapas Serang)',
            'anggota_tpp' => json_encode(['Kasubsi Pengelolaan', 'Ka. KPR', 'Bimkemaswat', 'Petugas Medis']),
            'nomor_berita_acara' => null,
            'file_berita_acara' => null,
            'file_dokumentasi' => null,
            'kanwil_hadir' => false,
            'nama_pengawas_kanwil' => null,
            'catatan_bintorwasdal' => null,
            'nomor_laporan_kanwil' => null,
            'tanggal_ttd_kakanwil' => null,
            'file_laporan_kakanwil' => null,
            'status_verifikasi_kanwil' => 'menunggu_sidang',
            'verified_by' => null,
            'verified_at' => null,
            'total_usulan' => 5,
            'total_disetujui' => 0,
            'total_ditunda' => 0,
            'total_ditolak' => 0,
            'total_setuju' => 0,
            'total_tunda' => 0,
            'total_tidak_setuju' => 0,
            'status_sidang' => 'dijadwalkan',
            'created_by' => $userPandeglang->id ?? 16,
        ]);

        $usulanSesi3 = [
            [
                'nama_wbp' => 'Ahmad Fauzi bin Rohmat',
                'nomor_register' => 'BI.301/2025',
                'perkara_pasal' => 'Pasal 363 KUHP (Pencurian Motor)',
                'lama_pidana' => '2 Tahun',
                'tanggal_ekspirasi' => '2027-05-10',
                'tanggal_setengah_pidana' => '2026-05-10',
                'tanggal_dua_pertiga' => '2026-09-10',
                'jenis_usulan' => 'PB',
                'skor_sppn' => 87.00,
                'status_litmas' => 'memenuhi_syarat',
                'bebas_register_f' => true,
                'perlu_perhatian' => false,
                'catatan_perhatian' => null,
                'keputusan' => 'belum_putus',
                'alasan_keterangan' => null,
            ],
            [
                'nama_wbp' => 'Rudi Hartono bin Sukardi',
                'nomor_register' => 'BI.312/2025',
                'perkara_pasal' => 'Pasal 372 KUHP (Penggelapan)',
                'lama_pidana' => '1 Tahun 6 Bulan',
                'tanggal_ekspirasi' => '2027-01-20',
                'tanggal_setengah_pidana' => '2026-01-20',
                'tanggal_dua_pertiga' => '2026-05-20',
                'jenis_usulan' => 'CB',
                'skor_sppn' => 85.50,
                'status_litmas' => 'memenuhi_syarat',
                'bebas_register_f' => true,
                'perlu_perhatian' => false,
                'catatan_perhatian' => null,
                'keputusan' => 'belum_putus',
                'alasan_keterangan' => null,
            ],
            [
                'nama_wbp' => 'Iwan Setiawan bin Bahar',
                'nomor_register' => 'BI.320/2025',
                'perkara_pasal' => 'Pasal 127 UU 35/2009 (Penyalahguna Narkotika)',
                'lama_pidana' => '2 Tahun',
                'tanggal_ekspirasi' => '2027-06-15',
                'tanggal_setengah_pidana' => '2026-06-15',
                'tanggal_dua_pertiga' => '2026-10-15',
                'jenis_usulan' => 'PB',
                'skor_sppn' => 89.00,
                'status_litmas' => 'memenuhi_syarat',
                'bebas_register_f' => true,
                'perlu_perhatian' => true,
                'catatan_perhatian' => 'WBP Narkotika - konfirmasi kelayakan penjamin & asimilasi pembinaan',
                'keputusan' => 'belum_putus',
                'alasan_keterangan' => null,
            ],
            [
                'nama_wbp' => 'Encep Solihin bin Darsum',
                'nomor_register' => 'BI.335/2025',
                'perkara_pasal' => 'Pasal 351 Ayat (1) KUHP (Penganiayaan)',
                'lama_pidana' => '1 Tahun 4 Bulan',
                'tanggal_ekspirasi' => '2026-12-05',
                'tanggal_setengah_pidana' => '2025-12-05',
                'tanggal_dua_pertiga' => '2026-04-05',
                'jenis_usulan' => 'CB',
                'skor_sppn' => 82.00,
                'status_litmas' => 'memenuhi_syarat',
                'bebas_register_f' => true,
                'keputusan' => 'belum_putus',
                'alasan_keterangan' => null,
            ],
            [
                'nama_wbp' => 'Yudi Prayoga bin Darsono',
                'nomor_register' => 'BI.348/2025',
                'perkara_pasal' => 'Pasal 378 KUHP (Penipuan Online)',
                'lama_pidana' => '1 Tahun 8 Bulan',
                'tanggal_ekspirasi' => '2027-04-12',
                'tanggal_setengah_pidana' => '2026-04-12',
                'tanggal_dua_pertiga' => '2026-08-12',
                'jenis_usulan' => 'Remisi',
                'skor_sppn' => 88.00,
                'status_litmas' => 'memenuhi_syarat',
                'bebas_register_f' => true,
                'keputusan' => 'belum_putus',
                'alasan_keterangan' => null,
            ],
        ];

        foreach ($usulanSesi3 as $item) {
            $sidang3->usulan()->create($item);
        }
        $sidang3->recalculateCounters();
    }

    /**
     * Buat file PDF valid 1 halaman sederhana secara native PHP.
     */
    private function generateDummyPdf(string $fullPath, string $title, string $subtitle): void
    {
        $safeTitle = addcslashes($title, '()\\');
        $safeSubtitle = addcslashes($subtitle, '()\\');

        $content = "%PDF-1.4\n".
            "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n".
            "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n".
            "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n".
            "4 0 obj << /Length 200 >> stream\n".
            "BT\n/F1 16 Tf\n50 780 Td\n({$safeTitle}) Tj\n".
            "/F1 11 Tf\n0 -28 Td\n({$safeSubtitle}) Tj\n".
            "/F1 10 Tf\n0 -25 Td\n(SULTAN BANTEN - Sistem Pengawasan dan Bintorwasdal Sidang TPP) Tj\n".
            "/F1 9 Tf\n0 -20 Td\n(Berdasarkan Surat Edaran Dirjenpas No: PAS-38.OT.02.02 Tahun 2026) Tj\n".
            "ET\nendstream\nendobj\n".
            "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >> endobj\n".
            "xref\n0 6\n0000000000 65535 f \n0000000009 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000227 00000 n \n0000000480 00000 n \n".
            "trailer << /Size 6 /Root 1 0 R >>\nstartxref\n560\n%%EOF";

        File::put($fullPath, $content);
    }

    /**
     * Buat file gambar JPG valid menggunakan ekstensi GD PHP (ukuran ringan ~50-80 KB).
     */
    private function generateDummyJpg(string $fullPath, string $caption, int $r, int $g, int $b): void
    {
        $width = 800;
        $height = 500;

        $img = imagecreatetruecolor($width, $height);
        $bgColor = imagecolorallocate($img, $r, $g, $b);
        imagefilledrectangle($img, 0, 0, $width, $height, $bgColor);

        // Header band
        $headerColor = imagecolorallocate($img, 255, 255, 255);
        $bandColor = imagecolorallocate($img, 15, 23, 42);
        imagefilledrectangle($img, 0, 0, $width, 80, $bandColor);

        // Teks header
        imagestring($img, 5, 30, 30, 'SULTAN BANTEN - DOKUMENTASI RESMI SIDANG TPP', $headerColor);

        // Body caption box
        $boxColor = imagecolorallocate($img, 255, 255, 255);
        $textColor = imagecolorallocate($img, 30, 41, 59);
        imagefilledrectangle($img, 40, 140, $width - 40, $height - 60, $boxColor);

        imagestring($img, 5, 60, 180, 'RUTAN KELAS IIB PANDEGLANG', $textColor);
        imagestring($img, 4, 60, 220, $caption, $textColor);
        imagestring($img, 3, 60, 270, 'Tanggal: '.date('d F Y').' | Terkompresi Otomatis <= 500 KB', $textColor);
        imagestring($img, 3, 60, 300, 'Surat Edaran Dirjenpas Nomor: PAS-38.OT.02.02 Tahun 2026', $textColor);

        imagejpeg($img, $fullPath, 85);
        imagedestroy($img);
    }
}
