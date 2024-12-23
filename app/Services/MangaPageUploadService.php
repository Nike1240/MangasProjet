<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class MangaPageUploadService
{
    public function uploadPage(UploadedFile $pageImage, int $index, string $chapterTitle)
    {
        $fileNameWithExt = $pageImage->getClientOriginalName();
        $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
        $extension = $pageImage->getClientOriginalExtension();
        $fileNameToStore = $fileName . '_' . time() . '_' . $index . '.' . $extension;
        
        // Store original image
        $imagePath = $pageImage->storeAs('public/manga_pages', $fileNameToStore);
        
        // Create and store thumbnail
        $thumbnail = Image::make($pageImage);
        $thumbnail->resize(300, null, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });
        
        $thumbnailName = 'thumb_' . $fileNameToStore;
        $thumbnailPath = 'public/manga_pages/thumbnails/' . $thumbnailName;
        Storage::put($thumbnailPath, $thumbnail->encode());

        return [
            'image_path' => $fileNameToStore,
            'thumbnail_path' => $thumbnailName,
            'width' => $thumbnail->width(),
            'height' => $thumbnail->height(),
            'file_size' => $pageImage->getSize(),
        ];
    }
}