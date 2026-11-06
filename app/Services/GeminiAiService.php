<?php

namespace App\Services;

use App\Models\AiSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAiService
{
    /**
     * Susun konfigurasi generationConfig yang kompatibel untuk model Gemini 1.5, 2.5, dan 3.x (termasuk 3.8 Flash).
     *
     * @param  array<string, mixed>  $extraConfig
     * @return array<string, mixed>
     */
    public static function buildGenerationConfig(string $chosenModel, array $extraConfig = []): array
    {
        $lower = strtolower($chosenModel);
        $config = array_merge([
            'temperature' => 0.1,
        ], $extraConfig);

        // Gemini 2.5 Flash mendukung thinkingBudget = 0 untuk menonaktifkan reasoning
        if (str_contains($lower, '2.5-flash')) {
            $config['thinkingConfig'] = [
                'thinkingBudget' => 0,
            ];
        }
        // Gemini 3.x (misal gemini-3.8-flash, gemini-3.7-flash) menggunakan thinkingLevel ('LOW', 'MEDIUM', 'HIGH')
        elseif (str_contains($lower, '3.') || str_contains($lower, '3-')) {
            $config['thinkingConfig'] = [
                'thinkingLevel' => 'LOW',
            ];
        }

        return $config;
    }

    /**
     * Ekstrak teks balasan utama dari candidates parts Gemini,
     * mengabaikan part internal reasoning/thought bila ada (misal pada Gemini 3.8 Flash).
     *
     * @param  array<string, mixed>|null  $candidate
     */
    public static function extractTextFromCandidate(?array $candidate): ?string
    {
        if (! $candidate || empty($candidate['content']['parts'])) {
            return null;
        }

        $parts = $candidate['content']['parts'];
        $nonThoughtTexts = [];

        foreach ($parts as $part) {
            // Pada Gemini 3.8 Flash dan model reasoning, internal thought ditandai dengan 'thought' => true
            if (empty($part['thought']) && isset($part['text']) && trim($part['text']) !== '') {
                $nonThoughtTexts[] = $part['text'];
            }
        }

        if (! empty($nonThoughtTexts)) {
            return implode("\n", $nonThoughtTexts);
        }

        // Fallback jika semua part dianggap thought atau format lama
        return $parts[count($parts) - 1]['text'] ?? ($parts[0]['text'] ?? null);
    }

    /**
     * Bersihkan teks dari format markdown ```json ... ``` agar aman di-decode oleh json_decode.
     */
    public static function cleanJsonPayload(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        $clean = trim($text);
        if (str_starts_with($clean, '```')) {
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
            $clean = preg_replace('/\s*```$/', '', $clean);
        }

        return trim($clean);
    }

    /**
     * Dapatkan daftar model fallback jika model utama sedang mengalami lonjakan beban (High Demand) di server Google.
     *
     * @return array<int, string>
     */
    public static function getFallbackModels(string $primaryModel): array
    {
        $fallbacks = ['gemini-2.5-flash', 'gemini-1.5-flash'];

        return array_values(array_filter($fallbacks, fn ($m) => $m !== $primaryModel));
    }

    /**
     * Cek apakah pesan error dari Google menandakan server overload / high demand.
     */
    public static function isHighDemandError(string $message, int $statusCode = 0): bool
    {
        $lower = strtolower($message);

        return $statusCode === 503
            || $statusCode === 429
            || str_contains($lower, 'high demand')
            || str_contains($lower, 'overloaded')
            || str_contains($lower, 'temporarily unavailable')
            || str_contains($lower, 'resource exhausted')
            || str_contains($lower, 'spikes in demand');
    }

    /**
     * Kirim permintaan ke Gemini API dengan dukungan auto-retry dan auto-fallback jika model utama mengalami High Demand.
     *
     * @param  array<int, array<string, mixed>>  $contents
     * @param  array<string, mixed>  $extraConfig
     * @return array{
     *   response: Response|null,
     *   model_used: string,
     *   is_fallback: bool,
     *   error: ?string
     * }
     */
    public static function postGenerateContentWithFallback(
        string $apiKey,
        string $primaryModel,
        array $contents,
        array $extraConfig = [],
        int $timeout = 60
    ): array {
        $modelsToTry = array_unique(array_merge([$primaryModel], self::getFallbackModels($primaryModel)));
        $lastError = null;

        foreach ($modelsToTry as $idx => $model) {
            $isFallback = ($model !== $primaryModel);
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
            $config = self::buildGenerationConfig($model, $extraConfig);

            try {
                $response = Http::timeout($timeout)->connectTimeout(15)->post($endpoint, [
                    'contents' => $contents,
                    'generationConfig' => $config,
                ]);

                if ($response->successful()) {
                    return [
                        'response' => $response,
                        'model_used' => $model,
                        'is_fallback' => $isFallback,
                        'error' => null,
                    ];
                }

                $errJson = $response->json();
                $rawMsg = $errJson['error']['message'] ?? ('HTTP '.$response->status().' '.$response->reason());
                $lastError = $rawMsg;

                // Jika error adalah high demand / overload / 503 / 429, coba model cadangan berikutnya
                if (self::isHighDemandError($rawMsg, $response->status()) && isset($modelsToTry[$idx + 1])) {
                    Log::warning("Gemini model '{$model}' mengalami high demand di Google ({$rawMsg}). Beralih otomatis ke model cadangan '{$modelsToTry[$idx + 1]}'.");

                    continue;
                }

                // Jika error jenis lain (misal API key invalid 400/403), tidak perlu dicoba ke model lain
                break;
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("Gemini model '{$model}' mengalami kendala jaringan: ".$e->getMessage());
                if (isset($modelsToTry[$idx + 1])) {
                    continue;
                }
            }
        }

        return [
            'response' => null,
            'model_used' => $primaryModel,
            'is_fallback' => false,
            'error' => $lastError ?? 'Gagal menghubungi server Google Gemini.',
        ];
    }

    /**
     * Uji koneksi ke Google Gemini API menggunakan API Key yang diberikan.
     *
     * @return array{
     *   success: bool,
     *   message: string,
     *   model: string,
     *   is_high_demand?: bool,
     *   suggested_model?: string,
     *   raw_error?: string
     * }
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

            $generationConfig = self::buildGenerationConfig($chosenModel, [
                'maxOutputTokens' => 1000,
            ]);

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
                $candidate = $body['candidates'][0] ?? null;
                $reply = self::extractTextFromCandidate($candidate) ?? 'Koneksi berhasil.';

                return [
                    'success' => true,
                    'message' => trim($reply),
                    'model' => $chosenModel,
                ];
            }

            $errBody = $response->json();
            $errMsg = $errBody['error']['message'] ?? ('HTTP '.$response->status().' '.$response->reason());
            $isHighDemand = self::isHighDemandError($errMsg, $response->status());

            if ($isHighDemand) {
                $fallbacks = self::getFallbackModels($chosenModel);
                $suggestedFallback = $fallbacks[0] ?? 'gemini-2.5-flash';

                return [
                    'success' => false,
                    'is_high_demand' => true,
                    'suggested_model' => $suggestedFallback,
                    'message' => "Model '{$chosenModel}' saat ini sedang mengalami lonjakan antrean trafik tinggi di server Google (High Demand). Lonjakan ini biasanya bersifat sementara di sisi Google.",
                    'raw_error' => $errMsg,
                    'model' => $chosenModel,
                ];
            }

            return [
                'success' => false,
                'is_high_demand' => false,
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

            $contents = [
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
            ];

            @set_time_limit(180);
            $res = self::postGenerateContentWithFallback(
                apiKey: $setting->api_key,
                primaryModel: $chosenModel,
                contents: $contents,
                extraConfig: ['responseMimeType' => 'application/json'],
                timeout: 75
            );

            if (! $res['response'] || ! $res['response']->successful()) {
                return [
                    'success' => false,
                    'message' => 'Gagal memproses PDF dengan AI: '.($res['error'] ?? 'Tidak ada balasan dari server Google.'),
                ];
            }

            $resJson = $res['response']->json();
            $candidate = $resJson['candidates'][0] ?? null;
            $rawText = self::extractTextFromCandidate($candidate) ?? '';
            $cleanedJson = self::cleanJsonPayload($rawText);

            $data = json_decode($cleanedJson, true);
            if (! is_array($data)) {
                return [
                    'success' => false,
                    'message' => 'Format balasan AI tidak dapat dibaca sebagai JSON.',
                ];
            }

            $msg = 'Surat undangan berhasil dianalisis oleh AI.';
            if ($res['is_fallback']) {
                $msg .= " (dialihkan otomatis ke model cadangan {$res['model_used']} karena {$chosenModel} sedang antre di server Google).";
            }

            return [
                'success' => true,
                'message' => $msg,
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

            $contents = [
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
            ];

            @set_time_limit(240);
            $res = self::postGenerateContentWithFallback(
                apiKey: $setting->api_key,
                primaryModel: $chosenModel,
                contents: $contents,
                extraConfig: ['responseMimeType' => 'application/json'],
                timeout: 90
            );

            if (! $res['response'] || ! $res['response']->successful()) {
                return [
                    'success' => false,
                    'message' => 'Gagal memproses Berita Acara dengan AI: '.($res['error'] ?? 'Tidak ada balasan dari server Google.'),
                ];
            }

            $resJson = $res['response']->json();
            $candidate = $resJson['candidates'][0] ?? null;
            $rawText = self::extractTextFromCandidate($candidate) ?? '';
            $cleanedJson = self::cleanJsonPayload($rawText);

            $data = json_decode($cleanedJson, true);
            if (! is_array($data)) {
                return [
                    'success' => false,
                    'message' => 'Format balasan AI tidak dapat dibaca sebagai JSON.',
                ];
            }

            $msg = 'Berita Acara berhasil dianalisis oleh AI Gemini.';
            if ($res['is_fallback']) {
                $msg .= " (dialihkan otomatis ke model cadangan {$res['model_used']} karena {$chosenModel} sedang antre di server Google).";
            }

            return [
                'success' => true,
                'message' => $msg,
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
