<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptCameraPasswords extends Command
{
    protected $signature = 'cameras:encrypt-passwords';

    protected $description = 'Encrypt existing plaintext camera passwords in the database';

    public function handle(): int
    {
        $cameras = DB::table('cameras')->whereNotNull('password')->where('password', '!=', '')->get();

        $encrypted = 0;
        $skipped = 0;

        foreach ($cameras as $camera) {
            // Check if already encrypted (encrypted strings start with 'eyJ')
            if (str_starts_with($camera->password, 'eyJ')) {
                $skipped++;
                continue;
            }

            DB::table('cameras')->where('id', $camera->id)->update([
                'password' => Crypt::encryptString($camera->password),
            ]);
            $encrypted++;
        }

        $this->info("Encrypted: {$encrypted}, Already encrypted: {$skipped}");

        return self::SUCCESS;
    }
}
