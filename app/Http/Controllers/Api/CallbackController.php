<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\VaApiLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    /**
     * Handle callback/webhook from Bank NTB Syariah.
     *
     * Endpoint: POST /api/callback/ntbsyariah
     *
     * This endpoint will be called by Bank NTB server when a real payment
     * is received for a Virtual Account.
     *
     * TODO: Configure this URL in Bank NTB merchant portal as the callback URL.
     * TODO: Verify the signature/token from Bank (check Bank NTB documentation for signature format).
     * TODO: Add IP whitelist for Bank NTB server IPs.
     * TODO: Add rate limiting to prevent abuse.
     */
    public function handleNtbSyariahCallback(Request $request)
    {
        // TODO: Verify signature/token from Bank
        // Check Bank NTB documentation for signature verification method
        // Common methods: HMAC-SHA256 signature in header, or token-based auth
        $signature = $request->header('X-Signature') ?? $request->header('signature');
        $secretKey = env('SECRET_KEY_SIGNATURE', '');

        // TODO: Uncomment and implement signature verification when Bank provides the method
        /*
        if ($signature && $secretKey) {
            $payload = $request->getContent();
            $expectedSignature = hash_hmac('sha256', $payload, $secretKey);
            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('Callback signature verification failed', [
                    'received_signature' => $signature,
                    'expected_signature' => $expectedSignature,
                ]);
                return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
            }
        }
        */

        // TODO: Validate required fields from Bank payload
        // Check Bank NTB documentation for exact payload structure
        $vaNumber = $request->input('va') ?? $request->input('va_number');
        $paymentStatus = $request->input('status') ?? $request->input('payment_status');
        $amount = $request->input('amount') ?? $request->input('jumlah_bayar');
        $paymentDate = $request->input('datetime_payment') ?? $request->input('payment_date');

        if (!$vaNumber) {
            return response()->json([
                'success' => false,
                'message' => 'VA number is required in callback payload',
            ], 400);
        }

        // TODO: Log the raw callback payload for debugging
        Log::info('Bank NTB Callback Received', [
            'va' => $vaNumber,
            'status' => $paymentStatus,
            'amount' => $amount,
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        try {
            DB::beginTransaction();

            // Find pembayaran by VA number
            $pembayaran = Pembayaran::where('va_number', $vaNumber)->first();

            if (!$pembayaran) {
                // TODO: Handle case where VA number not found
                // This could mean the VA was created outside our system
                // Log for manual investigation
                Log::warning('Callback received for unknown VA number', [
                    'va' => $vaNumber,
                ]);

                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'VA number not found',
                ], 404);
            }

            // TODO: Check if payment status indicates success
            // Bank NTB may use different status values: 'paid', 'lunas', 'success', '000', etc.
            $isPaid = in_array(strtolower($paymentStatus ?? ''), ['paid', 'lunas', 'success', '000', 'completed']);

            if ($isPaid) {
                // Update pembayaran status to 'dikonfirmasi' (confirmed/paid)
                $pembayaran->update([
                    'status' => 'dikonfirmasi',
                    'verified_at' => now(),
                ]);

                // Update tagihan status to 'sudah_dibayar' (paid)
                if ($pembayaran->tagihan) {
                    $pembayaran->tagihan->update(['status' => 'sudah_dibayar']);
                }

                // Log the callback processing
                VaApiLog::create([
                    'endpoint' => 'callback-ntb-syariah',
                    'success' => true,
                    'status_code' => 200,
                    'rcode' => '000',
                    'message' => 'Payment confirmed via callback',
                    'request_data' => $request->all(),
                    'response_data' => [
                        'va' => $pembayaran->va_number,
                        'status' => 'dikonfirmasi',
                        'amount' => $pembayaran->jumlah_bayar,
                    ],
                    'duration_ms' => 0,
                ]);
            }

            // TODO: Handle other payment statuses (failed, pending, etc.)
            // if ($paymentStatus === 'failed') {
            //     $pembayaran->update(['status' => 'ditolak']);
            // }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Callback processed successfully',
                'data' => [
                    'va' => $pembayaran->va_number,
                    'status' => $pembayaran->status,
                    'amount' => $pembayaran->jumlah_bayar,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Callback processing failed', [
                'va' => $vaNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process callback: ' . $e->getMessage(),
            ], 500);
        }
    }
}
