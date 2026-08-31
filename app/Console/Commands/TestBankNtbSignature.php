<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestBankNtbSignature extends Command
{
    protected $signature = 'bankntb:test-signature';
    protected $description = 'Test Bank NTB signature generation';

    public function handle()
    {
        $secretKey = env('SECRET_KEY_SIGNATURE', '');
        $this->info("SECRET_KEY_SIGNATURE: [{$secretKey}]");
        $this->info("SECRET_KEY_SIGNATURE length: " . strlen($secretKey));

        $payload = [
            'va' => '25060110057',
            'id_mitra' => '031',
            'id_produk' => '01',
            'name' => 'Anang Zikri Rahmatullah',
            'billing_type' => 'c',
            'email' => '25060110057@ubt.ac.id',
            'phone' => null,
            'datetime_expired' => now()->format('Y-m-d H:i:s'),
            'description' => 'Pembayaran UKT Tagihan UKT 2026/2027 semester 3',
            'tagihan' => '5000000',
        ];

        $rawJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->info("\n=== RAW JSON ===");
        $this->info($rawJson);
        $this->info("JSON length: " . strlen($rawJson));

        $this->info("\n=== SIGNATURE (correct: HMAC rawJson) ===");
        $correctSig = hash_hmac('sha256', $rawJson, $secretKey);
        $this->info($correctSig);

        $this->info("\n=== SIGNATURE (old WRONG: HMAC secret+rawJson) ===");
        $wrongSig = hash_hmac('sha256', $secretKey . $rawJson, $secretKey);
        $this->info($wrongSig);

        $this->info("\n=== VERIFY: Re-encode and check ===");
        $reEncoded = json_encode(json_decode($rawJson, true), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->info("Original == Re-encoded: " . ($rawJson === $reEncoded ? 'YES' : 'NO'));
        if ($rawJson !== $reEncoded) {
            $this->error("Original: " . $rawJson);
            $this->error("Re-encoded: " . $reEncoded);
        }

        return Command::SUCCESS;
    }
}
