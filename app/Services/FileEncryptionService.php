<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class FileEncryptionService
{
    public static function encryptAndStore($filePath)
    {
        // Si $filePath est un chemin de fichier
        if (is_string($filePath)) {
            $fileContent = Storage::get($filePath);
            $encryptedPath = 'downloads/encrypted/' . basename($filePath);
        } 
        // Si $filePath est un fichier uploadé
        elseif (is_object($filePath) && method_exists($filePath, 'getRealPath')) {
            $fileContent = File::get($filePath->getRealPath());
            $encryptedPath = 'downloads/encrypted/' . $filePath->getClientOriginalName();
        } else {
            throw new \InvalidArgumentException('Type de fichier non supporté');
        }

        Storage::put($encryptedPath, encrypt($fileContent));

        return $encryptedPath;
    }
}
