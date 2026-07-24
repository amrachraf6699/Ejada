<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Encoders\PngEncoder;

class CertificateImageService
{
    public function storeBackground(UploadedFile $file): array
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());
        $path = 'certificate-templates/' . Str::uuid() . '.png';
        Storage::disk('public')->put($path, $image->encode(new PngEncoder())->toString());

        return ['path' => $path, 'width' => $image->width(), 'height' => $image->height()];
    }

    public function storeIssuedPng(string $dataUrl): string
    {
        if (! preg_match('/^data:image\/png;base64,(.+)$/s', $dataUrl, $matches)) {
            throw new \InvalidArgumentException('صيغة صورة الشهادة غير صحيحة.');
        }

        $binary = base64_decode($matches[1], true);
        if ($binary === false) {
            throw new \InvalidArgumentException('تعذر قراءة صورة الشهادة.');
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->read($binary);
        $path = 'certificates/' . Str::uuid() . '.png';
        Storage::disk('public')->put($path, $image->encode(new PngEncoder())->toString());
        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path) Storage::disk('public')->delete($path);
    }
}
