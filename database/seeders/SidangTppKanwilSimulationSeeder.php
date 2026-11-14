<?php

namespace Database\Seeders;

use App\Models\KanwilPegawai;
use App\Models\SidangTppKanwil;
use App\Models\SidangTppKanwilAnggota;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SidangTppKanwilSimulationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Data Personil Tim TPP Kantor Wilayah
        $pegawaiData = [
            [
                'nama' => 'Surya Dharma, Bc.IP., S.H.',
                'nip' => '197408151998031001',
                'jabatan' => 'Kepala Divisi Pemasyarakatan',
                'pangkat_golongan' => 'Pembina Utama Muda (IV/c)',
                'urutan' => 1,
                'no_wa' => '081298765431',
                'peran_tpp' => 'Ketua',
                'is_active' => true,
            ],
            [
                'nama' => 'Rizal Rivai, S.H.',
                'nip' => '197903212002121001',
                'jabatan' => 'Kepala Bidang Pelayanan Tahanan, Pembinaan & TI',
                'pangkat_golongan' => 'Pembina Tk.I (IV/b)',
                'urutan' => 2,
                'no_wa' => '081311223344',
                'peran_tpp' => 'Sekretaris',
                'is_active' => true,
            ],
            [
                'nama' => 'Gunawan Wibisono, S.Sos., M.Si.',
                'nip' => '198205122005011002',
                'jabatan' => 'Kepala Subbidang Pembinaan, TI & Kerja Sama',
                'pangkat_golongan' => 'Pembina (IV/a)',
                'urutan' => 3,
                'no_wa' => '081288990011',
                'peran_tpp' => 'Anggota',
                'is_active' => true,
            ],
            [
                'nama' => 'Indra Sanjaya Putra, S.H., M.H.',
                'nip' => '198509182008011001',
                'jabatan' => 'Kepala Subbidang Pengelolaan Basan Baran & Keamanan',
                'pangkat_golongan' => 'Penata Tk.I (III/d)',
                'urutan' => 4,
                'no_wa' => '081755667788',
                'peran_tpp' => 'Anggota',
                'is_active' => true,
            ],
            [
                'nama' => 'Adang Ruswandi, S.AP.',
                'nip' => '198004142003121002',
                'jabatan' => 'Pembimbing Kemasyarakatan Ahli Madya',
                'pangkat_golongan' => 'Pembina (IV/a)',
                'urutan' => 5,
                'no_wa' => '081922334455',
                'peran_tpp' => 'Anggota',
                'is_active' => true,
            ],
            [
                'nama' => 'Eka Aditya Permana, S.Tr.Pas.',
                'nip' => '199602112019011001',
                'jabatan' => 'Penyusun Laporan Hasil Sidang TPP Kanwil',
                'pangkat_golongan' => 'Penata Muda (III/a)',
                'urutan' => 6,
                'no_wa' => '083870170049',
                'peran_tpp' => 'Anggota',
                'is_active' => true,
            ],
        ];

        $pegawaiModels = [];
        foreach ($pegawaiData as $pData) {
            $pegawai = KanwilPegawai::updateOrCreate(
                ['nip' => $pData['nip']],
                $pData
            );
            $pegawaiModels[] = $pegawai;
        }

        // Ambil user pembuat (admin / kanwil)
        $creator = User::where('role', 'admin')->first() ?? User::first();

        // Hapus sidang simulasi lama jika ada agar tidak duplikat
        SidangTppKanwil::where('nomor_sidang', 'TPP-KW/2026/XI/004')->delete();

        // 2. Buat Sidang TPP Kanwil Simulasi
        $sidang = SidangTppKanwil::create([
            'nomor_sidang' => 'TPP-KW/2026/XI/004',
            'tanggal_sidang' => now()->format('Y-m-d'),
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '11:45:00',
            'tempat' => 'Ruang Rapat Utama Divisi Pemasyarakatan Kantor Wilayah Kemenkumham Banten',
            'metode' => 'hybrid',
            'link_daring' => 'https://meet.google.com/tpp-kanwil-banten',
            'meeting_id' => '892 4102 7711',
            'passcode' => 'KANWIL2026',
            'nomor_nota_dinas' => 'W.12.PAS.UM.01.01-1402',
            'tanggal_nota_dinas' => now()->subDays(2)->format('Y-m-d'),
            'agenda_kategori' => 'Integrasi PB/CB',
            'agenda_judul' => 'Sidang TPP Kanwil Ke-IV: Evaluasi Usulan Pemindahan WBP Risiko Tinggi dan Telaah Teknis Rekomendasi Pembebasan Bersyarat (PB)',
            'agenda_deskripsi' => 'Pembahasan pertimbangan teknis usulan mutasi narapidana kategori risiko tinggi antar-UPT wilayah Banten serta evaluasi berkas usulan Pembebasan Bersyarat (PB) dan Cuti Menjelang Bebas (CMB) yang memerlukan rekomendasi khusus tingkat wilayah.',
            'ketua_id' => $pegawaiModels[0]->id,
            'ketua_nama' => $pegawaiModels[0]->nama,
            'ketua_nip' => $pegawaiModels[0]->nip,
            'ketua_jabatan' => $pegawaiModels[0]->jabatan,
            'sekretaris_id' => $pegawaiModels[1]->id,
            'sekretaris_nama' => $pegawaiModels[1]->nama,
            'sekretaris_nip' => $pegawaiModels[1]->nip,
            'sekretaris_jabatan' => $pegawaiModels[1]->jabatan,
            'total_anggota' => 6,
            'total_hadir' => 6,
            'total_setuju' => 5,
            'total_setuju_catatan' => 1,
            'total_tolak' => 0,
            'total_tunda' => 0,
            'kesimpulan_sidang' => 'Berdasarkan hasil musyawarah deliberasi Tim Sidang TPP Kantor Wilayah, seluruh anggota (6 personil) menyatakan DAPAT DISETUJUI dengan rekomendasi tindak lanjut berupa pemenuhan catatan teknis pengawalan maksimum saat pemindahan wargabinaan risiko tinggi dan monitoring evaluasi bersama Bapas setempat.',
            'nomor_berita_acara' => 'W.12.PAS.UM.01.04-328',
            'nomor_risalah' => 'WP.12.PK.06.05-28',
            'nomor_laporan' => 'WP.12.PK.06.06-28',
            'tanggapan_kakanwil_status' => 'menunggu',
            'kakanwil_nama' => 'Dody Fahrudin, Bc.IP., S.H., M.H.',
            'kakanwil_nip' => '197205141994031001',
            'status_sidang' => 'menunggu_kakanwil',
            'created_by' => $creator?->id,
            'updated_by' => $creator?->id,
        ]);

        // 3. Susunan Anggota Sidang & Simulasi Tanggapan/Pendapat
        $simulasiAnggota = [
            [
                'pegawai' => $pegawaiModels[0],
                'peran_sidang' => 'Ketua',
                'no_wa' => '081298765431',
                'status_kehadiran' => 'hadir_luring',
                'keterangan_kehadiran' => 'Hadir langsung memimpin jalannya sidang TPP di Ruang Rapat Divisi Pemasyarakatan.',
                'waktu_kehadiran' => now()->setTime(8, 55),
                'status_pendapat' => 'setuju',
                'catatan_pendapat' => 'Seluruh berkas persyaratan substantif dan administratif wargabinaan telah memenuhi ketentuan Permenkumham No. 7 Tahun 2022. Program pembinaan kepribadian dan kemandirian menunjukkan tren positif.',
                'waktu_pendapat' => now()->setTime(10, 15),
            ],
            [
                'pegawai' => $pegawaiModels[1],
                'peran_sidang' => 'Sekretaris',
                'no_wa' => '081311223344',
                'status_kehadiran' => 'hadir_luring',
                'keterangan_kehadiran' => 'Hadir langsung mencatat jalannya sidang dan rekapitulasi berkas perkara.',
                'waktu_kehadiran' => now()->setTime(8, 58),
                'status_pendapat' => 'setuju',
                'catatan_pendapat' => 'Hasil penelitian kemasyarakatan (Litmas) dari Bapas Serang dan Bapas Ciangir merekomendasikan bahwa program pembinaan lanjutan dan integrasi layak untuk disetujui.',
                'waktu_pendapat' => now()->setTime(10, 20),
            ],
            [
                'pegawai' => $pegawaiModels[2],
                'peran_sidang' => 'Anggota',
                'no_wa' => '081288990011',
                'status_kehadiran' => 'hadir_luring',
                'keterangan_kehadiran' => 'Hadir langsung di ruang sidang.',
                'waktu_kehadiran' => now()->setTime(9, 2),
                'status_pendapat' => 'setuju',
                'catatan_pendapat' => 'Penilaian SPPN (Sistem Penilaian Pembinaan Narapidana) menunjukkan kepatuhan tinggi terhadap tata tertib Lapas, nilai rata-rata pembinaan di atas standar minimum (kategori Baik).',
                'waktu_pendapat' => now()->setTime(10, 30),
            ],
            [
                'pegawai' => $pegawaiModels[3],
                'peran_sidang' => 'Anggota',
                'no_wa' => '081755667788',
                'status_kehadiran' => 'hadir_daring',
                'keterangan_kehadiran' => 'Hadir secara daring melalui Google Meet karena sedang monitoring fisik UPT wilayah Tangerang.',
                'waktu_kehadiran' => now()->setTime(9, 5),
                'status_pendapat' => 'setuju_catatan',
                'catatan_pendapat' => 'Menyetujui usulan dengan catatan: untuk narapidana risiko tinggi wajib diberlakukan SOP pengawalan standar maksimum saat pemindahan dan koordinasi ketat dengan aparat penegak hukum terkait.',
                'waktu_pendapat' => now()->setTime(10, 42),
            ],
            [
                'pegawai' => $pegawaiModels[4],
                'peran_sidang' => 'Anggota',
                'no_wa' => '081922334455',
                'status_kehadiran' => 'hadir_luring',
                'keterangan_kehadiran' => 'Hadir langsung di ruang sidang.',
                'waktu_kehadiran' => now()->setTime(9, 1),
                'status_pendapat' => 'setuju',
                'catatan_pendapat' => 'Asesmen risiko pengulangan tindak pidana menunjukkan kategori rendah. Penjamin keluarga telah diverifikasi langsung oleh Pembimbing Kemasyarakatan dan sanggup mendampingi.',
                'waktu_pendapat' => now()->setTime(10, 50),
            ],
            [
                'pegawai' => $pegawaiModels[5],
                'peran_sidang' => 'Anggota',
                'no_wa' => '083870170049',
                'status_kehadiran' => 'hadir_luring',
                'keterangan_kehadiran' => 'Hadir langsung dan menyusun matriks administrasi vonis ekspirasi.',
                'waktu_kehadiran' => now()->setTime(8, 50),
                'status_pendapat' => 'setuju',
                'catatan_pendapat' => 'Seluruh parameter vonis, perhitungan 2/3 masa pidana, dan data perolehan remisi di SDP telah dikroscek dengan dokumen kutipan putusan pengadilan.',
                'waktu_pendapat' => now()->setTime(11, 0),
            ],
        ];

        foreach ($simulasiAnggota as $item) {
            SidangTppKanwilAnggota::create([
                'sidang_tpp_kanwil_id' => $sidang->id,
                'pegawai_id' => $item['pegawai']->id,
                'user_id' => null,
                'token_akses' => Str::random(40),
                'nama' => $item['pegawai']->nama,
                'nip' => $item['pegawai']->nip,
                'jabatan' => $item['pegawai']->jabatan,
                'no_wa' => $item['no_wa'],
                'peran_sidang' => $item['peran_sidang'],
                'status_kehadiran' => $item['status_kehadiran'],
                'keterangan_kehadiran' => $item['keterangan_kehadiran'],
                'waktu_kehadiran' => $item['waktu_kehadiran'],
                'status_pendapat' => $item['status_pendapat'],
                'catatan_pendapat' => $item['catatan_pendapat'],
                'waktu_pendapat' => $item['waktu_pendapat'],
                'wa_sent_status' => 'terkirim',
                'wa_sent_at' => now()->subHours(4),
                'wa_error_message' => null,
            ]);
        }

        $sidang->recalculateStats();

        // 4. Simulasi Sidang TPP Kanwil Kasus Khusus: KLIEN PEMASYARAKATAN (BAPAS) - Izin Umroh ke Luar Negeri
        SidangTppKanwil::where('nomor_sidang', 'TPP-KW/2026/XI/005')->delete();

        $sidangKlien = SidangTppKanwil::create([
            'nomor_sidang' => 'TPP-KW/2026/XI/005',
            'tanggal_sidang' => now()->format('Y-m-d'),
            'jam_mulai' => '13:30:00',
            'jam_selesai' => '15:15:00',
            'tempat' => 'Ruang Rapat Utama Divisi Pemasyarakatan Kantor Wilayah Banten',
            'metode' => 'hybrid',
            'link_daring' => 'https://meet.google.com/tpp-kanwil-banten',
            'meeting_id' => '892 4102 7711',
            'passcode' => 'KANWIL2026',
            'nomor_nota_dinas' => 'WP.12.UM.01.01-1405',
            'tanggal_nota_dinas' => now()->subDay()->format('Y-m-d'),
            'nomor_risalah' => 'WP.12.PK.06.05-29',
            'nomor_laporan' => 'WP.12.PK.06.06-29',
            'agenda_kategori' => 'Izin Bepergian ke Luar Negeri untuk Beribadah (Umroh / Haji)',
            'agenda_judul' => 'Sidang TPP Kanwil Ke-V: Permohonan Izin Bepergian ke Luar Negeri untuk Beribadah Umroh Klien PB Bapas Kelas I Serang an. H. AHMAD FAUZI BIN ZULKARNAIN',
            'agenda_deskripsi' => 'Pembahasan dan telaah substantif surat usulan Kepala Bapas Kelas I Serang perihal izin bepergian ke luar negeri (Kerajaan Arab Saudi) dalam rangka ibadah umroh selama 14 hari bagi Klien Pembebasan Bersyarat (PB) an. H. AHMAD FAUZI BIN ZULKARNAIN.',
            'is_klien_bapas' => true,
            'klien_data' => [
                'nama_klien' => 'H. AHMAD FAUZI',
                'bin' => 'ZULKARNAIN',
                'no_register_bapas' => 'BPS-SRG/PB/2026/042',
                'bapas_nama' => 'Balai Pemasyarakatan Kelas I Serang',
                'nomor_surat_bapas' => 'WP.12.PAS.BAPAS-124',
                'tanggal_surat_bapas' => now()->subDays(3)->format('Y-m-d'),
                'nama_pk' => 'Adang Ruswandi, S.AP.',
                'program_integrasi' => 'Pembebasan Bersyarat (PB)',
                'keperluan_ln' => 'Menjalankan Ibadah Umroh',
                'negara_tujuan' => 'Kerajaan Arab Saudi',
                'tgl_berangkat' => now()->addDays(20)->format('Y-m-d'),
                'tgl_kembali' => now()->addDays(34)->format('Y-m-d'),
                'durasi_hari' => 14,
                'penjamin_nama' => 'Hj. Siti Aminah, S.Pd.',
                'penjamin_hubungan' => 'Istri Kandung',
                'penjamin_telepon' => '081287654321',
            ],
            'ketua_id' => $pegawaiModels[0]->id,
            'ketua_nama' => $pegawaiModels[0]->nama,
            'ketua_nip' => $pegawaiModels[0]->nip,
            'ketua_jabatan' => $pegawaiModels[0]->jabatan,
            'sekretaris_id' => $pegawaiModels[1]->id,
            'sekretaris_nama' => $pegawaiModels[1]->nama,
            'sekretaris_nip' => $pegawaiModels[1]->nip,
            'sekretaris_jabatan' => $pegawaiModels[1]->jabatan,
            'total_anggota' => 6,
            'total_hadir' => 6,
            'total_setuju' => 6,
            'total_setuju_catatan' => 0,
            'total_tolak' => 0,
            'total_tunda' => 0,
            'kesimpulan_sidang' => 'Tim Sidang TPP Kantor Wilayah secara mufakat MENYETUJUI dan MEREKOMENDASIKAN permohonan izin bepergian ke luar negeri (Arab Saudi) untuk keperluan ibadah umroh selama 14 hari kepada Klien PB Bapas Serang an. H. AHMAD FAUZI BIN ZULKARNAIN. Klien diwajibkan melapor ke Bapas paling lambat 3 hari kerja setelah tiba di tanah air.',
            'nomor_berita_acara' => 'BA-TPP-KW/2026/11/005',
            'tanggapan_kakanwil_status' => 'menunggu',
            'kakanwil_nama' => 'Dody Fahrudin, Bc.IP., S.H., M.H.',
            'kakanwil_nip' => '197205141994031001',
            'status_sidang' => 'menunggu_kakanwil',
            'created_by' => $creator?->id,
            'updated_by' => $creator?->id,
        ]);

        foreach ($simulasiAnggota as $item) {
            SidangTppKanwilAnggota::create([
                'sidang_tpp_kanwil_id' => $sidangKlien->id,
                'pegawai_id' => $item['pegawai']->id,
                'user_id' => null,
                'token_akses' => Str::random(40),
                'nama' => $item['pegawai']->nama,
                'nip' => $item['pegawai']->nip,
                'jabatan' => $item['pegawai']->jabatan,
                'no_wa' => $item['no_wa'],
                'peran_sidang' => $item['peran_sidang'],
                'status_kehadiran' => 'hadir_luring',
                'keterangan_kehadiran' => 'Hadir dalam persidangan deliberasi izin bepergian luar negeri.',
                'waktu_kehadiran' => now()->setTime(13, 25),
                'status_pendapat' => 'setuju',
                'catatan_pendapat' => 'Klien telah menjalani 1/2 masa bimbingan dengan rekam jejak tertib, Litmas Bapas memenuhi syarat, dan surat jaminan istri bermaterai sah. Layak disetujui.',
                'waktu_pendapat' => now()->setTime(14, 30),
                'wa_sent_status' => 'terkirim',
                'wa_sent_at' => now()->subHours(2),
                'wa_error_message' => null,
            ]);
        }

        $sidangKlien->recalculateStats();
    }
}
