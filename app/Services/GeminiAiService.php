<?php

namespace App\Services;

use App\Models\AiSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAiService
{
    /**
     * Uji koneksi ke Google Gemini API menggunakan API Key yang diberikan.
     *
     * @return array{success: bool, message: string, model: string}
     */
    public static function testConnection(?string $apiKey = null, ?string $model = null): array
    {
        $setting = AiSetting::getFirst();
        $key = $apiKey ?: $setting->api_key;
        $chosenModel = $model ?: ($setting->model ?: 'gemini-3.8-flash');

        if (empty($key)) {
            return [
                'success' => false,
                'message' => 'API Key Gemini belum diisi. Silakan masukkan API Key dari Google AI Studio.',
                'model' => $chosenModel,
            ];
        }

        try {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$chosenModel}:generateContent?key={$key}";

            $generationConfig = [
                'temperature' => 0.1,
                'maxOutputTokens' => 60,
            ];

            if (str_contains(strtolower($chosenModel), '2.5-flash')) {
                $generationConfig['thinkingConfig'] = [
                    'thinkingBudget' => 0,
                ];
            }

            $response = Http::timeout(25)->connectTimeout(10)->post($endpoint, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Halo! Ini pengujian koneksi dari Sistem Sultan Banten (Kanwil Banten). Balas dengan kalimat pendek: "Koneksi Google Gemini API berhasil terhubung."'],
                        ],
                    ],
                ],
                'generationConfig' => $generationConfig,
            ]);

            if ($response->successful()) {
                $body = $response->json();
                $reply = $body['candidates'][0]['content']['parts'][0]['text'] ?? 'Koneksi berhasil.';

                return [
                    'success' => true,
                    'message' => trim($reply),
                    'model' => $chosenModel,
                ];
            }

            $errBody = $response->json();
            $errMsg = $errBody['error']['message'] ?? ('HTTP '.$response->status().' '.$response->reason());

            return [
                'success' => false,
                'message' => 'Gagal terhubung ke Gemini API: '.$errMsg,
                'model' => $chosenModel,
            ];
        } catch (\Throwable $e) {
            Log::error('Gemini API testConnection failed: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan jaringan atau timeout: '.$e->getMessage(),
                'model' => $chosenModel,
            ];
        }
    }

    /**
     * Mengambil daftar model yang tersedia untuk API Key yang diberikan.
     *
     * @return array{success: bool, message: string, models: array<int, array{id: string, name: string, description: string}>}
     */
    public static function getAvailableModels(?string $apiKey = null): array
    {
        $setting = AiSetting::getFirst();
        $key = $apiKey ?: $setting->api_key;

        if (empty($key)) {
            return [
                'success' => false,
                'message' => 'API Key belum diisi. Masukkan API Key terlebih dahulu untuk memuat daftar model.',
                'models' => [],
            ];
        }

        try {
            $response = Http::timeout(15)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$key}");

            if (! $response->successful()) {
                $err = $response->json();
                $msg = $err['error']['message'] ?? ('HTTP '.$response->status().' '.$response->reason());

                return [
                    'success' => false,
                    'message' => 'Gagal mengambil daftar model: '.$msg,
                    'models' => [],
                ];
            }

            $json = $response->json();
            $rawModels = $json['models'] ?? [];
            $models = [];

            foreach ($rawModels as $m) {
                $methods = $m['supportedGenerationMethods'] ?? [];
                if (in_array('generateContent', $methods, true)) {
                    $cleanId = str_replace('models/', '', $m['name'] ?? '');
                    $models[] = [
                        'id' => $cleanId,
                        'name' => $m['displayName'] ?? $cleanId,
                        'description' => $m['description'] ?? '',
                    ];
                }
            }

            return [
                'success' => true,
                'message' => 'Berhasil memuat '.count($models).' model aktif dari Google AI Studio.',
                'models' => $models,
            ];
        } catch (\Throwable $e) {
            Log::error('Gemini getAvailableModels error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Terjadi kesalahan: '.$e->getMessage(),
                'models' => [],
            ];
        }
    }

    /**
     * Ekstraksi rincian surat undangan sidang TPP dari berkas PDF via Gemini AI.
     *
     * @return array{
     *   success: bool,
     *   message: string,
     *   data?: array{
     *     nomor_undangan: string,
     *     tanggal_surat: string,
     *     hal: string,
     *     asal_upt: string,
     *     hari_tanggal_sidang: string,
     *     jam_mulai: string,
     *     tempat: string
     *   }
     * }
     */
    public static function extractUndanganPdf(string $pdfPath): array
    {
        $setting = AiSetting::getFirst();

        if (! $setting->is_active || empty($setting->api_key)) {
            return [
                'success' => false,
                'message' => 'Integrasi AI belum diaktifkan atau API Key belum diisi di Pengaturan Master Data. Silakan isi form secara manual.',
            ];
        }

        $fullPath = public_path($pdfPath);
        if (! file_exists($fullPath)) {
            return [
                'success' => false,
                'message' => 'Berkas PDF surat undangan tidak ditemukan di server.',
            ];
        }

        if (filesize($fullPath) > 20971520) { // 20 MB
            return [
                'success' => false,
                'message' => 'Ukuran berkas PDF melebihi batas 20 MB untuk pemrosesan AI.',
            ];
        }

        try {
            $base64Data = base64_encode(file_get_contents($fullPath));
            $chosenModel = ! empty($setting->model) ? $setting->model : 'gemini-3.8-flash';
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$chosenModel}:generateContent?key={$setting->api_key}";

            $prompt = <<<'PROMPT'
Anda adalah asisten cerdas tata naskah dinas Kementerian Imigrasi dan Pemasyarakatan RI (Ditjenpas / Kantor Wilayah).
Tugas Anda adalah membaca dan menganalisis berkas PDF Surat Undangan Sidang TPP yang dilampirkan.
Ekstrak data penting berikut dalam format JSON:
- "nomor_undangan": nomor surat undangan resmi UPT (misal: WP.12.PAS7/PK.06.05/17/2026 atau sejenisnya)
- "tanggal_surat": tanggal surat dibuat (format teks Indonesia, misal: "05 November 2026" atau format tanggal yang tertulis di surat)
- "hal": perihal surat (misal: "Undangan Sidang TPP")
- "asal_upt": nama instansi/UPT pengirim surat (misal: "Lembaga Pembinaan Khusus Anak Kelas I Tangerang" atau "Lapas Pemuda Kelas IIA Tangerang")
- "hari_tanggal_sidang": hari dan tanggal pelaksanaan kegiatan sidang TPP (misal: "Jumat, 06 November 2026")
- "jam_mulai": jam atau waktu pelaksanaan (misal: "10.00 WIB s.d selesai" atau "09.00 s.d selesai")
- "tempat": tempat, ruangan, atau tautan daring sidang (misal: "Ruang Kampus Lapas Pemuda Kelas IIA Tangerang" atau "Zoom Cloud Meeting / Virtual")

Hanya kembalikan objek JSON valid tanpa penjelasan tambahan.
PROMPT;

            $generationConfig = [
                'temperature' => 0.1,
                'responseMimeType' => 'application/json',
            ];

            if (str_contains(strtolower($chosenModel), '2.5-flash')) {
                $generationConfig['thinkingConfig'] = [
                    'thinkingBudget' => 0,
                ];
            }

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => 'application/pdf',
                                    'data' => $base64Data,
                                ],
                            ],
                        ],
                    ],
                ],
                'generationConfig' => $generationConfig,
            ];

            @set_time_limit(120);
            $response = Http::timeout(60)->connectTimeout(15)->post($endpoint, $payload);

            if (! $response->successful()) {
                $err = $response->json();
                $msg = $err['error']['message'] ?? ('HTTP '.$response->status());

                return [
                    'success' => false,
                    'message' => 'Gagal memproses PDF dengan AI: '.$msg,
                ];
            }

            $resJson = $response->json();
            $rawText = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? '';

            $data = json_decode($rawText, true);
            if (! is_array($data)) {
                return [
                    'success' => false,
                    'message' => 'Format balasan AI tidak dapat dibaca sebagai JSON.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Surat undangan berhasil dianalisis oleh AI.',
                'data' => [
                    'nomor_undangan' => $data['nomor_undangan'] ?? '',
                    'tanggal_surat' => $data['tanggal_surat'] ?? '',
                    'hal' => $data['hal'] ?? 'Undangan Sidang TPP',
                    'asal_upt' => $data['asal_upt'] ?? '',
                    'hari_tanggal_sidang' => $data['hari_tanggal_sidang'] ?? '',
                    'jam_mulai' => $data['jam_mulai'] ?? '',
                    'tempat' => $data['tempat'] ?? '',
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('Gemini extractUndanganPdf error: '.$e->getMessage());

            $errMsg = $e->getMessage();
            if (str_contains($errMsg, 'timed out') || str_contains($errMsg, 'cURL error 28')) {
                $errMsg = 'Waktu analisis AI habis (timeout). Silakan periksa ukuran PDF atau lanjutkan dengan pengisian form secara manual.';
            }

            return [
                'success' => false,
                'message' => 'Kendala Analisis AI: '.$errMsg,
            ];
        }
    }

    /**
     * Analisis cerdas berkas PDF Berita Acara (BA) Sidang TPP dari UPT via Gemini AI
     * untuk menyusun draf Laporan Pelaksanaan Sidang TPP Kantor Wilayah yang adaptif sesuai materi sidang.
     *
     * @param  array<string, mixed>  $metaData
     * @return array{
     *   success: bool,
     *   message: string,
     *   data?: array<string, mixed>
     * }
     */
    public static function analyzeBeritaAcaraPdf(string $pdfPath, array $metaData = []): array
    {
        $setting = AiSetting::getFirst();

        if (! $setting->is_active || empty($setting->api_key)) {
            return [
                'success' => false,
                'message' => 'Integrasi AI belum diaktifkan atau API Key belum diisi di Pengaturan Master Data AI.',
            ];
        }

        $fullPath = public_path($pdfPath);
        if (! file_exists($fullPath)) {
            return [
                'success' => false,
                'message' => 'Berkas PDF Berita Acara sidang TPP tidak ditemukan di server.',
            ];
        }

        if (filesize($fullPath) > 20971520) { // 20 MB
            return [
                'success' => false,
                'message' => 'Ukuran berkas PDF melebihi batas 20 MB untuk pemrosesan AI.',
            ];
        }

        try {
            $base64Data = base64_encode(file_get_contents($fullPath));
            $chosenModel = ! empty($setting->model) ? $setting->model : 'gemini-3.8-flash';
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$chosenModel}:generateContent?key={$setting->api_key}";

            $uptNama = $metaData['upt_nama'] ?? 'UPT Pemasyarakatan';
            $hariTgl = $metaData['hari_tanggal_sidang'] ?? '';
            $metode = $metaData['metode_sidang'] ?? 'virtual Zoom Meeting';
            $pengawas = $metaData['nama_pengawas'] ?? 'Perwakilan Kantor Wilayah';

            $prompt = <<<PROMPT
Anda adalah asisten cerdas tata naskah dinas Kantor Wilayah Kementerian Hukum dan HAM / Kementerian Imigrasi dan Pemasyarakatan RI.
Tugas Anda adalah membaca dan menganalisis berkas PDF Berita Acara (BA) Sidang Tim Pengamat Pemasyarakatan (TPP) yang dilampirkan dari satuan kerja: "{$uptNama}".
Konteks Sidang:
- Hari / Tanggal: {$hariTgl}
- Metode Pelaksanaan: {$metode}
- Pengawas / Perwakilan Kanwil: {$pengawas}

Susun draf resmi "LAPORAN PELAKSANAAN SIDANG TPP" Kantor Wilayah yang ADAPTIF dan SESUAI FAKTA materi sidang di dalam Berita Acara tersebut (apakah mengenai Remisi, Integrasi PB/CB, Asimilasi, Mutasi/Pemindahan, atau Penjatuhan Hukuman Disiplin).

Kembalikan respon DALAM FORMAT JSON VALID dengan struktur persis seperti ini:
{
  "topik_sidang": "ringkasan 1 baris topik sidang, misal: Usulan Pemberian Hak Integrasi (PB dan CB)",
  "materi_sidang_items": [
    "butir 1 rincian usulan beserta jumlah narapidana, misal: Pembebasan Bersyarat (PB): 33 orang narapidana",
    "butir 2..."
  ],
  "paragraf_a1": "Pada hari ..., Kantor Wilayah ... mengikuti pelaksanaan Sidang Tim Pengamat Pemasyarakatan (TPP) ... di {$uptNama}.",
  "paragraf_a2": "Sidang TPP membahas usulan ... terhadap narapidana yang telah diusulkan oleh {$uptNama}.",
  "paragraf_a3": "Perwakilan Kantor Wilayah hadir sebagai unsur pembina dan pengawas untuk melakukan verifikasi terhadap pemenuhan persyaratan substantif maupun administratif sesuai ketentuan peraturan perundang-undangan.",
  "paragraf_a4": "Perwakilan Kantor Wilayah melakukan pencermatan terhadap kelengkapan dokumen usulan, data registrasi narapidana, perhitungan masa pidana, laporan perkembangan pembinaan, penilaian risiko, serta dokumen pendukung lainnya yang dipersyaratkan dalam Sistem Database Pemasyarakatan (SDP).",
  "paragraf_b1": "Berdasarkan hasil pelaksanaan Sidang Tim Pengamat Pemasyarakatan (TPP) {$uptNama} pada tanggal ..., Tim Kantor Wilayah melakukan verifikasi terhadap usulan ... sesuai materi sidang.",
  "paragraf_b2_poin": [
    "Seluruh Narapidana yang dibahas telah memenuhi persyaratan substantif...",
    "Seluruh persyaratan administratif telah lengkap dan sesuai dengan ketentuan serta telah tersedia dalam Sistem Database Pemasyarakatan (SDP).",
    "Perhitungan masa pidana, tanggal 2/3 masa pidana, dan tanggal ekspirasi telah sesuai dengan data register narapidana.",
    "Dengan terpenuhinya persyaratan substantif dan administratif, usulan tersebut direkomendasikan untuk diproses lebih lanjut sesuai mekanisme yang berlaku."
  ],
  "simpulan_poin": [
    "Pelaksanaan Sidang Tim Pengamat Pemasyarakatan berjalan dengan tertib, lancar, dan sesuai dengan ketentuan yang berlaku.",
    "Pembahasan dilakukan secara objektif dengan mempertimbangkan aspek administratif, substantif, keamanan, serta hasil pembinaan terhadap masing-masing narapidana."
  ],
  "saran_poin": [
    "{$uptNama} agar tetap meningkatkan ketelitian dalam melakukan verifikasi administrasi sebelum usulan diajukan kepada Kantor Wilayah.",
    "Seluruh dokumen pendukung agar dipastikan telah lengkap, valid, dan diunggah ke dalam Sistem Database Pemasyarakatan (SDP) sebelum proses pengusulan dilanjutkan.",
    "Jika ada kendala, agar segera berkoordinasi dengan Kantor Wilayah.",
    "Monitoring terhadap pelaksanaan usulan agar terus dilakukan sesuai ketentuan peraturan perundang-undangan guna menjamin tercapainya tujuan Sistem Pemasyarakatan."
  ],
  "penutup": "Demikian laporan pelaksanaan Sidang Tim Pengamat Pemasyarakatan ini disusun sebagai bentuk pertanggungjawaban pelaksanaan tugas serta sebagai bahan informasi bagi pimpinan dalam rangka pembinaan dan pengawasan penyelenggaraan tugas di lingkungan Pemasyarakatan."
}

PENTING:
- Sesuaikan isi narasi dengan fakta di dalam PDF Berita Acara yang terlampir. Jika sidangnya bukan tentang PB/CB (misal tentang Remisi atau Mutasi), gantikan teks yang sesuai dengan isi BA tersebut.
- Hanya kembalikan teks JSON valid tanpa awalan atau akhiran markdown penjelasan.
PROMPT;

            $generationConfig = [
                'temperature' => 0.1,
                'responseMimeType' => 'application/json',
            ];

            if (str_contains(strtolower($chosenModel), '2.5-flash')) {
                $generationConfig['thinkingConfig'] = [
                    'thinkingBudget' => 0,
                ];
            }

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => 'application/pdf',
                                    'data' => $base64Data,
                                ],
                            ],
                        ],
                    ],
                ],
                'generationConfig' => $generationConfig,
            ];

            @set_time_limit(180);
            $response = Http::timeout(90)->connectTimeout(15)->post($endpoint, $payload);

            if (! $response->successful()) {
                $err = $response->json();
                $msg = $err['error']['message'] ?? ('HTTP '.$response->status());

                return [
                    'success' => false,
                    'message' => 'Gagal memproses Berita Acara dengan AI: '.$msg,
                ];
            }

            $resJson = $response->json();
            $rawText = $resJson['candidates'][0]['content']['parts'][0]['text'] ?? '';

            $data = json_decode($rawText, true);
            if (! is_array($data)) {
                return [
                    'success' => false,
                    'message' => 'Format balasan AI tidak dapat dibaca sebagai JSON.',
                ];
            }

            return [
                'success' => true,
                'message' => 'Berita Acara berhasil dianalisis oleh AI Gemini.',
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('Gemini analyzeBeritaAcaraPdf error: '.$e->getMessage());

            $errMsg = $e->getMessage();
            if (str_contains($errMsg, 'timed out') || str_contains($errMsg, 'cURL error 28')) {
                $errMsg = 'Waktu analisis AI habis (timeout). Berkas PDF Berita Acara mungkin terlalu tebal/banyak halaman, atau server Google AI sedang lambat. Silakan gunakan template draf yang sudah tersedia dan lengkapi secara manual, atau coba beberapa saat lagi.';
            }

            return [
                'success' => false,
                'message' => 'Kendala Analisis AI: '.$errMsg,
            ];
        }
    }
}
