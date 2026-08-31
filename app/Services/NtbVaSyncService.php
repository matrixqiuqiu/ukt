<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\RiwayatTransaksi;
use App\Models\VaApiLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NtbVaSyncService
{
    public function handleBankCallback(array $payload, array $headers, string $rawContent): array
    {
        Log::channel('bankntb')->info('Bank NTB Callback received', [
            'payload' => $payload,
            'headers' => $headers,
        ]);

        try {
            $va = $this->extractVa($payload);
            $rcode = $payload['rCode'] ?? $payload['rcode'] ?? null;
            $statusBayar = $payload['status_bayar'] ?? $payload['status'] ?? null;

            if (!$va) {
                return $this->buildResult(false, 'VA number not found in payload', 400, [
                    'rCode' => $rcode ?? '',
                ]);
            }

            $pembayaran = Pembayaran::with(['tagihan', 'metodePembayaran'])
                ->where('va_number', $va)
                ->first();

            if (!$pembayaran) {
                Log::channel('bankntb')->warning('Callback for unknown VA', ['va' => $va]);
                return $this->buildResult(false, 'Pembayaran not found for VA: ' . $va, 200, [
                    'rCode' => $rcode ?? '',
                    'va' => $va,
                ]);
            }

            if ($pembayaran->status === 'dikonfirmasi') {
                Log::channel('bankntb')->info('Payment already confirmed', ['va' => $va]);
                return $this->buildResult(true, 'Already processed', 200, [
                    'rCode' => $rcode ?? '',
                    'va' => $va,
                    'processed' => true,
                    'is_paid' => true,
                    'status_bank' => 'lunas',
                    'transaction_id' => $pembayaran->id,
                ]);
            }

            $isPaid = $this->determinePaidStatus($rcode, $statusBayar, $payload);

            if (!$isPaid) {
                Log::channel('bankntb')->info('Callback indicates not paid', [
                    'va' => $va,
                    'rCode' => $rcode,
                    'status_bayar' => $statusBayar,
                ]);
                return $this->buildResult(true, 'Payment not completed', 200, [
                    'rCode' => $rcode ?? '',
                    'va' => $va,
                    'processed' => false,
                    'is_paid' => false,
                    'status_bank' => 'pending',
                    'transaction_id' => $pembayaran->id,
                ]);
            }

            $this->confirmPayment($pembayaran, $payload);

            Log::channel('bankntb')->info('Payment confirmed via callback', [
                'pembayaran_id' => $pembayaran->id,
                'va' => $va,
            ]);

            return $this->buildResult(true, 'Payment confirmed', 200, [
                'rCode' => $rcode ?? '',
                'va' => $va,
                'processed' => true,
                'is_paid' => true,
                'status_bank' => 'lunas',
                'transaction_id' => $pembayaran->id,
            ]);
        } catch (\Exception $e) {
            Log::channel('bankntb')->error('Callback processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->buildResult(false, 'Internal error: ' . $e->getMessage(), 500, [
                'rCode' => $payload['rCode'] ?? $payload['rcode'] ?? '',
            ]);
        }
    }

    private function extractVa(array $payload): ?string
    {
        $va = $payload['va'] ?? $payload['no_va'] ?? $payload['virtual_account'] ?? null;
        if (is_string($va) && $va !== '') {
            return trim($va);
        }
        return null;
    }

    private function determinePaidStatus(?string $rcode, ?string $statusBayar, array $payload): bool
    {
        if ($rcode === '000') {
            return true;
        }

        $paidStatuses = ['lunas', 'paid', 'success', 'berhasil', 'settlement'];
        if ($statusBayar && in_array(strtolower($statusBayar), $paidStatuses, true)) {
            return true;
        }

        $statusFromPayload = $payload['status'] ?? null;
        if ($statusFromPayload && in_array(strtolower($statusFromPayload), $paidStatuses, true)) {
            return true;
        }

        return false;
    }

    private function confirmPayment(Pembayaran $pembayaran, array $payload): void
    {
        DB::transaction(function () use ($pembayaran, $payload) {
            $pembayaran->update([
                'status' => 'dikonfirmasi',
                'verified_at' => now(),
            ]);

            $tagihan = $pembayaran->tagihan;
            if ($tagihan) {
                $totalPaid = $tagihan->pembayarans()
                    ->where('status', 'dikonfirmasi')
                    ->sum('jumlah_bayar');

                if ($totalPaid >= $tagihan->nominal) {
                    $tagihan->update(['status' => 'lunas']);
                }
            }

            RiwayatTransaksi::create([
                'pembayaran_id' => $pembayaran->id,
                'user_id' => $pembayaran->tagihan->mahasiswa->user_id ?? 1,
                'aksi' => 'pembayaran_dikonfirmasi',
                'keterangan' => 'Pembayaran dikonfirmasi via Bank NTB callback. VA: '
                    . $pembayaran->va_number . ' | rCode: ' . ($payload['rCode'] ?? '-'),
            ]);

            VaApiLog::create([
                'endpoint' => 'callback/bankntb',
                'success' => true,
                'status_code' => 200,
                'rcode' => $payload['rCode'] ?? null,
                'message' => 'Callback confirmed',
                'request_data' => ['va' => $pembayaran->va_number],
                'response_data' => $payload,
            ]);
        });
    }

    private function buildResult(bool $success, string $message, int $httpStatus, array $extra = []): array
    {
        return array_merge([
            'success' => $success,
            'message' => $message,
            'http_status' => $httpStatus,
            'rCode' => '',
            'acknowledged' => true,
            'processed' => false,
            'is_paid' => false,
            'status_bank' => 'pending',
            'transaction_id' => 0,
            'va' => '',
        ], $extra);
    }
}
