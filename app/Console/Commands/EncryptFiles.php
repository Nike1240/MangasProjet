<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class EncryptFiles extends Command
{
    protected $signature = 'files:encrypt';
    protected $description = 'Crypte et stocke des fichiers de manière sécurisée';

    public function handle()
    {
        $files = Storage::files('uploads'); // Répertoire source des fichiers
        foreach ($files as $filePath) {
            $fileContent = Storage::get($filePath);

            // Chemin de destination pour le fichier crypté
            $encryptedPath = 'downloads/encrypted/' . basename($filePath);

            // Cryptage et stockage
            Storage::put($encryptedPath, encrypt($fileContent));

            // Supprimez l'original si nécessaire
            Storage::delete($filePath);

            $this->info("Fichier crypté : $encryptedPath");
        }
    }
}
