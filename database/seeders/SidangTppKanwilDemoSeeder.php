<?php

namespace Database\Seeders;

use App\Models\KanwilPegawai;
use App\Models\MutasiNarapidana;
use App\Models\MutasiNarapidanaTujuan;
use App\Models\SidangTppKanwil;
use App\Models\SidangTppKanwilAnggota;
use App\Models\SidangTppKanwilMateri;
use App\Models\Upt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SidangTppKanwilDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pastikan berkas contoh di public/uploads/mutasi tersedia
        $mutasiDir = public_path('uploads/mutasi');
        if (! File::isDirectory($mutasiDir)) {
            File::makeDirectory($mutasiDir, 0755, true, true);
        }

        $creator = User::where('role', 'admin')->first() ?? User::first();

        // 2. Buat Usulan Mutasi Narapidana #1: Kolektif Lapas Cilegon (28 Orang)
        $mutasi1 = MutasiNarapidana::updateOrCreate(
            ['nomor_surat' => 'W12.PAS.10-PK.01.04-452'],
            [
                'tanggal_surat' => Carbon::now()->subDays(2),
                'upt_asal_id' => 10, // Lapas Kelas IIA Cilegon
                'upt_tujuan_id' => 6, // Lapas Kelas IIA Serang
                'kategori_mutasi' => 'Overkapasitas & Redistribusi Pembinaan Lanjutan',
                'keterangan' => 'Permohonan Pemindahan 28 Orang Narapidana Overkapasitas Lapas Kelas IIA Cilegon untuk Redistribusi dan Pembinaan Lanjutan ke Lapas se-Wilayah Banten',
                'jumlah_narkotika' => 18,
                'jumlah_pidum' => 8,
                'jumlah_korupsi' => 2,
                'total_usulan' => 28,
                'file_surat_usulan' => 'uploads/mutasi/surat_usulan_cilegon_452.pdf',
                'file_lampiran_wbp' => 'uploads/mutasi/lampiran_nominatif_cilegon_28wbp.xlsx',
                'file_bap' => 'uploads/mutasi/ba_sidang_tpp_cilegon.pdf',
                'nomor_ba_tpp_upt' => 'W12.PAS.10.PK.04.01-88',
                'file_ba_tpp_upt' => 'uploads/mutasi/ba_sidang_tpp_cilegon.pdf',
                'status' => 'diajukan',
                'created_by' => $creator?->id,
            ]
        );

        // Multi-tujuan UPT untuk usulan kolektif
        MutasiNarapidanaTujuan::where('mutasi_id', $mutasi1->id)->delete();
        MutasiNarapidanaTujuan::create([
            'mutasi_id' => $mutasi1->id,
            'upt_tujuan_id' => 6, // Lapas Kelas IIA Serang
            'jumlah_disetujui_narkotika' => 10,
            'jumlah_disetujui_pidum' => 4,
            'jumlah_disetujui_korupsi' => 0,
            'total_disetujui' => 14,
            'status' => 'diajukan',
        ]);
        MutasiNarapidanaTujuan::create([
            'mutasi_id' => $mutasi1->id,
            'upt_tujuan_id' => 8, // Lapas Kelas III Rangkasbitung
            'jumlah_disetujui_narkotika' => 5,
            'jumlah_disetujui_pidum' => 4,
            'jumlah_disetujui_korupsi' => 0,
            'total_disetujui' => 9,
            'status' => 'diajukan',
        ]);
        MutasiNarapidanaTujuan::create([
            'mutasi_id' => $mutasi1->id,
            'upt_tujuan_id' => 9, // Lapas Ciangir
            'jumlah_disetujui_narkotika' => 3,
            'jumlah_disetujui_pidum' => 0,
            'jumlah_disetujui_korupsi' => 2,
            'total_disetujui' => 5,
            'status' => 'diajukan',
        ]);

        // 3. Buat Usulan Mutasi Narapidana #2: Mandiri an. Sugeng Riyadi bin Harun
        $mutasi2 = MutasiNarapidana::updateOrCreate(
            ['nomor_surat' => 'W12.PAS.1-PK.01.04-188'],
            [
                'tanggal_surat' => Carbon::now()->subDays(4),
                'upt_asal_id' => 1, // Lapas Kelas I Tangerang
                'upt_tujuan_id' => null,
                'kategori_mutasi' => 'Permintaan Sendiri Mendekati Keluarga (Biaya Mandiri)',
                'keterangan' => 'Permohonan Pemindahan Narapidana an. Sugeng Riyadi bin Harun ke Lapas Besi Nusakambangan Mendekati Keluarga',
                'jumlah_narkotika' => 1,
                'jumlah_pidum' => 0,
                'jumlah_korupsi' => 0,
                'total_usulan' => 1,
                'file_surat_usulan' => 'uploads/mutasi/surat_usulan_cilegon_452.pdf',
                'file_bap' => 'uploads/mutasi/ba_sidang_tpp_cilegon.pdf',
                'nomor_ba_tpp_upt' => 'W12.PAS.1.PK.04.01-112',
                'file_ba_tpp_upt' => 'uploads/mutasi/ba_sidang_tpp_cilegon.pdf',
                'status' => 'diajukan',
                'created_by' => $creator?->id,
            ]
        );

        // 4. Pastikan Personil Pegawai Kanwil Lengkap
        $pegawais = KanwilPegawai::orderBy('urutan')->get();
        if ($pegawais->isEmpty()) {
            $pegawaiData = [
                ['nama' => 'Surya Dharma, Bc.IP., S.H.', 'nip' => '197408151998031001', 'jabatan' => 'Kepala Divisi Pemasyarakatan', 'peran_tpp' => 'ketua', 'urutan' => 1, 'is_active' => true],
                ['nama' => 'Rizal Rivai, S.H.', 'nip' => '197903212002121001', 'jabatan' => 'Kepala Bidang Pelayanan Tahanan, Pembinaan & TI', 'peran_tpp' => 'sekretaris', 'urutan' => 2, 'is_active' => true],
                ['nama' => 'Gunawan Wibisono, S.Sos., M.Si.', 'nip' => '198205122005011002', 'jabatan' => 'Kepala Subbidang Pembinaan, TI & Kerja Sama', 'peran_tpp' => 'anggota', 'urutan' => 3, 'is_active' => true],
                ['nama' => 'Indra Sanjaya Putra, S.H., M.H.', 'nip' => '198509182008011001', 'jabatan' => 'Kepala Subbidang Pengelolaan Basan Baran & Keamanan', 'peran_tpp' => 'anggota', 'urutan' => 4, 'is_active' => true],
                ['nama' => 'Adang Ruswandi, S.AP.', 'nip' => '198004142003121002', 'jabatan' => 'Pembimbing Kemasyarakatan Ahli Madya', 'peran_tpp' => 'anggota', 'urutan' => 5, 'is_active' => true],
                ['nama' => 'Eka Aditya Permana, S.Tr.Pas.', 'nip' => '199602112019011001', 'jabatan' => 'Penyusun Laporan Hasil Sidang TPP Kanwil', 'peran_tpp' => 'anggota', 'urutan' => 6, 'is_active' => true],
            ];
            foreach ($pegawaiData as $pd) {
                KanwilPegawai::create($pd);
            }
            $pegawais = KanwilPegawai::orderBy('urutan')->get();
        }

        $ketua = $pegawais->firstWhere('peran_tpp', 'ketua') ?? $pegawais->first();
        $sekretaris = $pegawais->firstWhere('peran_tpp', 'sekretaris') ?? $pegawais->get(1);

        // 5. Buat Sesi Sidang TPP Kanwil Siap Sidang (STPP-KW/2026/XI/008)
        SidangTppKanwil::where('nomor_sidang', 'STPP-KW/2026/XI/008')->delete();

        $sidang = SidangTppKanwil::create([
            'nomor_sidang' => 'STPP-KW/2026/XI/008',
            'tanggal_sidang' => Carbon::now()->addDay()->toDateString(),
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '12:00:00',
            'tempat' => 'Ruang Rapat Utama Divisi Pemasyarakatan Kanwil Kemenkumham Banten',
            'metode' => 'luring',
            'nomor_nota_dinas' => 'ND-088/W12.PAS/PK.04.01/XI/2026',
            'tanggal_nota_dinas' => Carbon::now()->subDay()->toDateString(),
            'agenda_kategori' => 'Multi-Materi (Pemindahan Narapidana, Izin Berobat & Klien Bapas)',
            'agenda_judul' => 'Sidang TPP Kanwil Pembahasan Pemindahan Narapidana Kolektif Cilegon, Mutasi Mandiri Nusakambangan, dan Izin Berobat',
            'agenda_deskripsi' => 'Sidang TPP Kantor Wilayah Banten dalam rangka pembahasan usulan pemindahan narapidana antar-UPT/wilayah serta izin berobat lanjutan ke rumah sakit rujukan luar wilayah.',
            'mutasi_id' => $mutasi1->id,
            'ketua_id' => $ketua?->id,
            'ketua_nama' => $ketua?->nama ?? 'Surya Dharma, Bc.IP., S.H.',
            'ketua_nip' => $ketua?->nip ?? '197408151998031001',
            'ketua_jabatan' => $ketua?->jabatan ?? 'Kepala Divisi Pemasyarakatan',
            'sekretaris_id' => $sekretaris?->id,
            'sekretaris_nama' => $sekretaris?->nama ?? 'Rizal Rivai, S.H.',
            'sekretaris_nip' => $sekretaris?->nip ?? '197903212002121001',
            'sekretaris_jabatan' => $sekretaris?->jabatan ?? 'Kepala Bidang Pelayanan Tahanan, Pembinaan & TI',
            'total_anggota' => $pegawais->count(),
            'total_hadir' => $pegawais->count(),
            'status_sidang' => 'undangan_terbit',
            'created_by' => $creator?->id,
        ]);

        // Hubungkan usulan mutasi dengan sidang kanwil
        $mutasi1->update(['sidang_tpp_kanwil_id' => $sidang->id]);

        // Tambah Anggota Sidang TPP Kanwil
        foreach ($pegawais as $peg) {
            $peran = match ($peg->peran_tpp) {
                'ketua' => 'Ketua',
                'sekretaris' => 'Sekretaris',
                default => 'Anggota',
            };

            SidangTppKanwilAnggota::create([
                'sidang_tpp_kanwil_id' => $sidang->id,
                'pegawai_id' => $peg->id,
                'token_akses' => Str::random(32),
                'nama' => $peg->nama,
                'nip' => $peg->nip,
                'jabatan' => $peg->jabatan,
                'no_wa' => $peg->no_wa ?? '081234567890',
                'peran_sidang' => $peran,
                'status_kehadiran' => 'hadir_luring',
                'waktu_kehadiran' => Carbon::now(),
                'status_pendapat' => 'belum_mengisi',
                'wa_sent_status' => 'terkirim',
                'wa_sent_at' => Carbon::now(),
            ]);
        }

        // 6. Buat 3 Materi Agenda Sidang Lengkap
        // Materi #1: Kolektif Cilegon (28 WBP) dengan alokasi multi-tujuan
        SidangTppKanwilMateri::create([
            'sidang_tpp_kanwil_id' => $sidang->id,
            'urutan' => 1,
            'kategori_materi' => 'mutasi_narapidana',
            'judul_materi' => 'Pemindahan Narapidana Kolektif Lapas Kelas IIA Cilegon (28 Orang)',
            'deskripsi_materi' => 'Pembahasan usulan pemindahan 28 orang narapidana overkapasitas Lapas Kelas IIA Cilegon untuk redistribusi dan pembinaan lanjutan ke Lapas Kelas IIA Serang, Lapas Kelas III Rangkasbitung, dan Lapas Kelas I Semarang.',
            'mutasi_id' => $mutasi1->id,
            'upt_tujuan_rekomendasi_id' => 6,
            'narapidana_data' => [
                'mutasi_id' => $mutasi1->id,
                'nama' => 'Kolektif Lapas Kelas IIA Cilegon (28 Orang)',
                'nomor_register' => 'Kolektif (28 WBP)',
                'tindak_pidana' => '18 Narkotika, 8 Pidum, 2 Korupsi',
                'lama_pidana' => 'Bervariasi (3 s.d. 12 Tahun)',
                'upt_asal_id' => 10,
                'upt_asal_nama' => 'LEMBAGA PEMASYARAKATAN KELAS IIA CILEGON',
                'upt_tujuan_id' => 6,
                'upt_tujuan_nama' => 'LEMBAGA PEMASYARAKATAN KELAS IIA SERANG',
                'nomor_surat_usulan' => 'W12.PAS.10-PK.01.04-452',
                'tanggal_surat_usulan' => Carbon::now()->subDays(2)->toDateString(),
                'kategori_mutasi' => 'Pemindahan Antar-UPT Overkapasitas',
                'total_usulan' => 28,
                'jumlah_usulan_narkotika' => 18,
                'jumlah_usulan_pidum' => 8,
                'jumlah_usulan_korupsi' => 2,
                'alasan_pemindahan' => 'Overkapasitas & Redistribusi Pembinaan Lanjutan',
                'total_disetujui' => 28,
                'total_disetujui_narkotika' => 18,
                'total_disetujui_pidum' => 8,
                'total_disetujui_korupsi' => 2,
                'alokasi' => [
                    [
                        'upt_tujuan_id' => 6,
                        'upt_tujuan_nama' => 'LEMBAGA PEMASYARAKATAN KELAS IIA SERANG',
                        'jumlah_narkotika' => 10,
                        'jumlah_pidum' => 4,
                        'jumlah_korupsi' => 0,
                        'total' => 14,
                    ],
                    [
                        'upt_tujuan_id' => 8,
                        'upt_tujuan_nama' => 'LEMBAGA PEMASYARAKATAN KELAS III RANGKASBITUNG',
                        'jumlah_narkotika' => 5,
                        'jumlah_pidum' => 4,
                        'jumlah_korupsi' => 0,
                        'total' => 9,
                    ],
                    [
                        'upt_tujuan_id' => null,
                        'upt_tujuan_nama' => 'Lapas Kelas I Semarang (Luar Wilayah)',
                        'jumlah_narkotika' => 3,
                        'jumlah_pidum' => 0,
                        'jumlah_korupsi' => 2,
                        'total' => 5,
                    ],
                ],
                'nomor_ba_tpp_upt' => 'W12.PAS.10.PK.04.01-88',
                'tanggal_ba_tpp_upt' => Carbon::now()->subDays(2)->toDateString(),
                'rekomendasi_tpp_upt' => 'Disetujui untuk diusulkan pemindahan ke Kanwil Kemenkumham Banten',
                'berkas' => [
                    'file_surat_usulan' => 'uploads/mutasi/surat_usulan_cilegon_452.pdf',
                    'file_lampiran_wbp' => 'uploads/mutasi/lampiran_nominatif_cilegon_28wbp.xlsx',
                    'file_ba_upt' => 'uploads/mutasi/ba_sidang_tpp_cilegon.pdf',
                    'surat_usulan' => 'uploads/mutasi/surat_usulan_cilegon_452.pdf',
                    'lampiran_wbp' => 'uploads/mutasi/lampiran_nominatif_cilegon_28wbp.xlsx',
                    'ba_upt' => 'uploads/mutasi/ba_sidang_tpp_cilegon.pdf',
                ],
            ],
            'rekomendasi_sidang' => 'disetujui',
            'catatan_rekomendasi' => 'Disetujui untuk dilaksanakan pemindahan narapidana secara bertahap sesuai kuota masing-masing Lapas tujuan.',
        ]);

        // Materi #2: Mutasi Permintaan Sendiri Mendekati Keluarga
        SidangTppKanwilMateri::create([
            'sidang_tpp_kanwil_id' => $sidang->id,
            'urutan' => 2,
            'kategori_materi' => 'mutasi_keinginan_sendiri',
            'judul_materi' => 'Mutasi Permintaan Sendiri (Mendekati Keluarga) an. Sugeng Riyadi bin Harun',
            'deskripsi_materi' => 'Permohonan pemindahan mandiri mendekati domisili keluarga dari Lapas Kelas I Tangerang ke Lapas Besi Nusakambangan dengan kesanggupan pembiayaan mandiri bermaterai.',
            'mutasi_id' => $mutasi2->id,
            'upt_tujuan_rekomendasi_id' => null,
            'narapidana_data' => [
                'mutasi_id' => $mutasi2->id,
                'nama' => 'Sugeng Riyadi bin Harun',
                'nomor_register' => 'BI.441/2024',
                'tindak_pidana' => 'Narkotika Ps 114 ayat (2)',
                'lama_pidana' => '8 Tahun Penjara',
                'upt_asal_id' => 1,
                'upt_asal_nama' => 'LEMBAGA PEMASYARAKATAN KELAS I TANGERANG',
                'upt_tujuan_id' => null,
                'upt_tujuan_nama' => 'Lapas Besi Nusakambangan (Jawa Tengah)',
                'alasan_pemindahan' => 'Mendekati keluarga di Cilacap dan sanggup menanggung biaya pemindahan secara mandiri',
                'nama_pk_bapas' => 'Drs. Bambang Sudarsono, M.Si.',
                'nip_pk_bapas' => '197805122003121001',
                'bapas_nama' => 'Bapas Kelas I Serang',
                'rekomendasi_litmas' => 'Memenuhi syarat administratif dan substantif, berkelakuan baik, risiko pengulangan pidana rendah',
                'pk_bapas' => [
                    'nama' => 'Drs. Bambang Sudarsono, M.Si.',
                    'nip' => '197805122003121001',
                    'bapas' => 'Bapas Kelas I Serang',
                    'rekomendasi' => 'Memenuhi syarat administratif dan substantif, berkelakuan baik, risiko pengulangan pidana rendah',
                ],
                'nomor_ba_tpp_upt' => 'W12.PAS.1.PK.04.01-112',
                'tanggal_ba_tpp_upt' => Carbon::now()->subDays(4)->toDateString(),
                'rekomendasi_tpp_upt' => 'Disetujui TPP Lapas Kelas I Tangerang untuk diusulkan ke Kanwil Banten',
                'tpp_upt' => [
                    'nomor_ba' => 'W12.PAS.1.PK.04.01-112',
                    'tanggal_ba' => Carbon::now()->subDays(4)->toDateString(),
                    'rekomendasi' => 'Disetujui TPP Lapas Kelas I Tangerang untuk diusulkan ke Kanwil Banten',
                ],
                'penjamin_nama' => 'Siti Nurhaliza',
                'penjamin_nik' => '3201124508890003',
                'penjamin_hubungan' => 'Istri Kandung',
                'penjamin_alamat' => 'Jl. Laut Cilacap Selatan No. 14, Kab. Cilacap, Jawa Tengah',
                'penjamin_kontak' => '081298765432',
                'penjamin' => [
                    'nama' => 'Siti Nurhaliza',
                    'nik' => '3201124508890003',
                    'hubungan' => 'Istri Kandung',
                    'alamat' => 'Jl. Laut Cilacap Selatan No. 14, Kab. Cilacap, Jawa Tengah',
                    'telepon' => '081298765432',
                ],
                'berkas' => [
                    'litmas' => 'uploads/mutasi/ba_sidang_tpp_cilegon.pdf',
                    'pernyataan_pribadi' => 'uploads/mutasi/surat_usulan_cilegon_452.pdf',
                    'ba_upt' => 'uploads/mutasi/ba_sidang_tpp_cilegon.pdf',
                    'jaminan' => 'uploads/mutasi/surat_usulan_cilegon_452.pdf',
                ],
            ],
            'rekomendasi_sidang' => 'disetujui',
            'catatan_rekomendasi' => 'Disetujui untuk diteruskan rekomendasi izin pemindahan antar-wilayah ke Direktorat Jenderal Pemasyarakatan.',
        ]);

        // Materi #3: Izin Berobat Keluar Kota
        SidangTppKanwilMateri::create([
            'sidang_tpp_kanwil_id' => $sidang->id,
            'urutan' => 3,
            'kategori_materi' => 'izin_berobat',
            'judul_materi' => 'Izin Berobat Rujukan Keluar Kota an. Hendra Wijaya bin Darmawan',
            'deskripsi_materi' => 'Permohonan rujukan medis bedah jantung ke RS Jantung Harapan Kita Jakarta dikarenakan keterbatasan fasilitas penanganan jantung di RSUD Banten.',
            'narapidana_data' => [
                'nama' => 'Hendra Wijaya bin Darmawan',
                'nomor_register' => 'B.II.098/2023',
                'tindak_pidana' => 'Penipuan Ps 378 KUHP',
                'lama_pidana' => '3 Tahun Penjara',
                'upt_asal_id' => 6,
                'upt_asal_nama' => 'LEMBAGA PEMASYARAKATAN KELAS IIA SERANG',
                'diagnosa_medis' => 'Penyakit Jantung Koroner berat (CAD 3-Vessel Disease) indikasi Operasi Bypass CABG',
                'dokter_penanggung_jawab' => 'dr. Anwar Mansyur, Sp.JP(K)',
                'rs_rujukan_nama' => 'RS Jantung & Pembuluh Darah Harapan Kita',
                'rs_rujukan_kota' => 'Jakarta Barat',
                'penjamin_nama' => 'Darmawan (Ayah Kandung)',
                'penjamin_hubungan' => 'Orang Tua',
                'penjamin_kontak' => '081377889900',
                'berkas' => [
                    'rekam_medis' => 'uploads/mutasi/surat_usulan_cilegon_452.pdf',
                    'rujukan' => 'uploads/mutasi/ba_sidang_tpp_cilegon.pdf',
                    'ba_upt' => 'uploads/mutasi/ba_sidang_tpp_cilegon.pdf',
                ],
            ],
            'rekomendasi_sidang' => 'disetujui',
            'catatan_rekomendasi' => 'Disetujui izin rujukan berobat ke RS Jantung Harapan Kita Jakarta dengan pengawalan melekat petugas Lapas dan kepolisian.',
        ]);
    }
}
