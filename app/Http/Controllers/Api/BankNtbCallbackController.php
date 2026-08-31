<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NtbVaSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BankNtbCallbackController extends Controller
{
    public function __invoke(Request $request, NtbVaSyncService $service): JsonResponse
    {
        $rawContent = $request->getContent();
        $payload = json_decode($rawContent, true);

        if (!is_array($payload) || $payload === []) {
            $payload = $request->all();
        }

        if (!is_array($payload)) {
            $payload = [];
        }

        $headers = [];
        foreach ($request->headers->all() as $key => $values) {
            $rawValue = is_array($values) ? (string) ($values[0] ?? '') : (string) $values;
            $headers[strtolower(trim((string) $key))] = trim($rawValue);
        }

        $result = $service->handleBankCallback(
            $payload,
            $headers,
            (string) $request->getContent()
        );

        $status = (int) ($result['http_status'] ?? 200);
        if ($status < 100 || $status > 599) {
            $status = 200;
        }

        return response()->json([
            'success' => (bool) ($result['success'] ?? false),
            'message' => (string) ($result['message'] ?? ''),
            'provider' => 'ntbva',
            'rCode' => (string) ($result['rCode'] ?? ''),
            'acknowledged' => true,
            'processed' => (bool) ($result['processed'] ?? false),
            'is_paid' => (bool) ($result['is_paid'] ?? false),
            'status_bank' => (string) ($result['status_bank'] ?? 'pending'),
            'transaction_id' => (int) ($result['transaction_id'] ?? 0),
            'va' => (string) ($result['va'] ?? ''),
        ], $status);
    }
}
