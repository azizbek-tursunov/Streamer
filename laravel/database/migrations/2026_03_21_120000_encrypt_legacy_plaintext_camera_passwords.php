<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Find and re-encrypt any legacy plaintext passwords
        $cameras = DB::table('cameras')->whereNotNull('password')->where('password', '!=', '')->get();

        foreach ($cameras as $camera) {
            try {
                // If decryption succeeds, it's already encrypted — skip
                Crypt::decryptString($camera->password);
            } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                // Plaintext password found — encrypt it
                DB::table('cameras')
                    ->where('id', $camera->id)
                    ->update(['password' => Crypt::encryptString($camera->password)]);
            }
        }
    }

    public function down(): void
    {
        // Cannot safely reverse encryption
    }
};
