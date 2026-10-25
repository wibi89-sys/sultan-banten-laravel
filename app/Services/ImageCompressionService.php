<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class ImageCompressionService
{
    /**
     * Kompres dan simpan satu file gambar menjadi JPG dengan ukuran maksimal 500 KB.
     *
     * @param  UploadedFile  $file  File gambar dari request
     * @param  string  $destinationFolder  Folder tujuan (relatif terhadap public_path, default 'uploads/sidang_tpp')
     * @param  int  $maxBytes  Maksimal ukuran file dalam bytes (default 500 KB = 512,000 bytes)
     * @return string Path relatif file yang tersimpan (misal: 'uploads/sidang_tpp/dok_123.jpg')
     */
    public static function compressToJpg(UploadedFile $file, string $destinationFolder = 'uploads/sidang_tpp', int $maxBytes = 512000): string
    {
        $uploadDir = (function_exists('app') && method_exists(app(), 'publicPath'))
            ? public_path($destinationFolder)
            : (dirname(__DIR__, 2).'/public/'.trim($destinationFolder, '/\\'));

        if (! is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $filename = 'dok_'.time().'_'.bin2hex(random_bytes(4)).'.jpg';
        $targetPath = $uploadDir.DIRECTORY_SEPARATOR.$filename;
        $relativePath = trim($destinationFolder, '/\\').'/'.$filename;

        // 1. Baca data gambar
        $imageData = @file_get_contents($file->getRealPath());
        if ($imageData === false) {
            // Fallback move biasa jika file_get_contents gagal
            $file->move($uploadDir, $filename);

            return $relativePath;
        }

        $srcImage = @imagecreatefromstring($imageData);
        if ($srcImage === false) {
            $file->move($uploadDir, $filename);

            return $relativePath;
        }

        // Ambil dimensi asli
        $origWidth = imagesx($srcImage);
        $origHeight = imagesy($srcImage);

        // 2. Skalakan dimensi jika resolusi terlalu besar (> 1600px)
        $maxDimension = 1600;
        $width = $origWidth;
        $height = $origHeight;

        if ($origWidth > $maxDimension || $origHeight > $maxDimension) {
            if ($origWidth >= $origHeight) {
                $width = $maxDimension;
                $height = (int) round(($origHeight / $origWidth) * $maxDimension);
            } else {
                $height = $maxDimension;
                $width = (int) round(($origWidth / $origHeight) * $maxDimension);
            }
        }

        // Buat canvas truecolor putih (untuk mengatasi transparansi PNG/WebP menjadi hitam)
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);

        // Copy dan resample gambar ke canvas baru
        imagecopyresampled($canvas, $srcImage, 0, 0, 0, 0, $width, $height, $origWidth, $origHeight);
        imagedestroy($srcImage);

        // 3. Kompresi bertahap menuju target <= 500 KB (512,000 bytes)
        $quality = 85;
        imagejpeg($canvas, $targetPath, $quality);

        // Loop penurunan kualitas jika ukuran file masih melebihi 500 KB
        while (filesize($targetPath) > $maxBytes && $quality > 25) {
            $quality -= 10;
            imagejpeg($canvas, $targetPath, $quality);
        }

        // Jika setelah quality 25 masih > 500 KB, resize dimensi lagi ke 1200px
        if (filesize($targetPath) > $maxBytes) {
            $newMax = 1100;
            $newWidth = (int) round(($width / max($width, $height)) * $newMax);
            $newHeight = (int) round(($height / max($width, $height)) * $newMax);

            $smallerCanvas = imagecreatetruecolor($newWidth, $newHeight);
            $whiteSmall = imagecolorallocate($smallerCanvas, 255, 255, 255);
            imagefilledrectangle($smallerCanvas, 0, 0, $newWidth, $newHeight, $whiteSmall);

            imagecopyresampled($smallerCanvas, $canvas, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagejpeg($smallerCanvas, $targetPath, 65);
            imagedestroy($smallerCanvas);
        }

        imagedestroy($canvas);

        return $relativePath;
    }

    /**
     * Kompres kumpulan beberapa file gambar menjadi array path JPG.
     *
     * @param  array<UploadedFile>|UploadedFile  $files
     * @return array<string>
     */
    public static function compressMultipleToJpg(mixed $files, string $destinationFolder = 'uploads/sidang_tpp', int $maxBytes = 512000): array
    {
        if (empty($files)) {
            return [];
        }

        if ($files instanceof UploadedFile) {
            return [static::compressToJpg($files, $destinationFolder, $maxBytes)];
        }

        $results = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $results[] = static::compressToJpg($file, $destinationFolder, $maxBytes);
            }
        }

        return $results;
    }
}
