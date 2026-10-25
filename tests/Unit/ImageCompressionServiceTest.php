<?php

namespace Tests\Unit;

use App\Services\ImageCompressionService;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class ImageCompressionServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        // Bersihkan file uji jika ada
        $testDir = dirname(__DIR__, 2).'/public/uploads/test_sidang_tpp';
        if (is_dir($testDir)) {
            $files = glob($testDir.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
            @rmdir($testDir);
        }

        parent::tearDown();
    }

    public function test_compress_to_jpg_converts_to_jpg_and_limits_size_under_500kb(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('Ekstensi GD tidak terpasang.');
        }

        // Buat gambar PNG uji berukuran cukup besar (1800 x 1200)
        $width = 1800;
        $height = 1200;
        $im = imagecreatetruecolor($width, $height);
        $red = imagecolorallocate($im, 220, 50, 50);
        imagefilledrectangle($im, 0, 0, $width, $height, $red);

        // Tambah pola gradasi acak agar ukuran file tidak terlalu kecil saat dikompres
        for ($i = 0; $i < 500; $i++) {
            $randColor = imagecolorallocate($im, rand(0, 255), rand(0, 255), rand(0, 255));
            imagefilledrectangle($im, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $randColor);
        }

        $tempPng = tempnam(sys_get_temp_dir(), 'test_img_').'.png';
        imagepng($im, $tempPng);
        imagedestroy($im);

        $uploadedFile = new UploadedFile($tempPng, 'foto_sidang.png', 'image/png', null, true);

        $resultPath = ImageCompressionService::compressToJpg($uploadedFile, 'uploads/test_sidang_tpp', 512000);

        // 1. Ekstensi harus .jpg
        $this->assertStringEndsWith('.jpg', $resultPath);

        // 2. File fisik harus ada
        $fullPath = dirname(__DIR__, 2).'/public/'.$resultPath;
        $this->assertFileExists($fullPath);

        // 3. Ukuran file harus maksimal 500 KB (512,000 bytes)
        $fileSize = filesize($fullPath);
        $this->assertLessThanOrEqual(512000, $fileSize, "Ukuran file ({$fileSize} bytes) melebihi batas 500 KB.");

        // 4. Pastikan file valid JPG
        $imageInfo = @getimagesize($fullPath);
        $this->assertNotFalse($imageInfo);
        $this->assertEquals(IMAGETYPE_JPEG, $imageInfo[2]);

        @unlink($tempPng);
    }
}
