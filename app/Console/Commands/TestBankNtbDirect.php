<?php

namespace App\Console\Commands;

use App\Services\VaNtbService;
use Illuminate\Console\Command;

class TestBankNtbDirect extends Command
{
    protected $signature = 'bankntb:test-direct';
    protected $description = 'Test VaNtbService directly (same as web flow)';

    public function handle()
    {
        $vaService = new VaNtbService();

        $this->info("=== 1. GET TOKEN ===");
        $tokenResult = $vaService->getToken();
        $this->info("Token success: " . ($tokenResult['success'] ? 'YES' : 'NO'));
        $this->info("Token message: {$tokenResult['message']}");
        if (!$tokenResult['success']) return 1;

        $this->info("\n=== 2. CREATE VA ===");
        $result = $vaService->createVa(
            '25060110077',
            'Anang Zikri Rahmatullah',
            5000000,
            'test@ubg.ac.id',
            '081234567890',
            'Pembayaran UKT Test'
        );
        $this->info("Success: " . ($result['success'] ? 'YES' : 'NO'));
        $this->info("rCode: {$result['rcode']}");
        $this->info("Message: {$result['message']}");
        $this->info("Response: " . json_encode($result['raw']));

        return 0;
    }
}
