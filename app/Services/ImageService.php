<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImageService
{
    public function storeProductImage(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        return $file->store('products', 'public');
    }

    public function replaceProductImage(?string $currentPath, ?UploadedFile $file): ?string
    {
        if (! $file) {
            return $currentPath;
        }

        if ($currentPath) {
            Storage::disk('public')->delete($currentPath);
        }

        return $this->storeProductImage($file);
    }

    public function deleteProductImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
