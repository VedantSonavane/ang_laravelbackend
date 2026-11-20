<?php

namespace App\Services;

use App\Exceptions\ApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OverdueClient
{
    private function http(): PendingRequest
    {
        return Http::baseUrl(config('overdue.url'))
            ->withHeaders(['X-Service-Key' => (string) config('overdue.key')])
            ->acceptJson()
            ->timeout(config('overdue.timeout'));
    }

    // Marks past-due tasks as OVERDUE. A failure here must not break reads.
    public function sync(): void
    {
        try {
            $this->http()->post('/api/overdue/sync')->throw();
        } catch (Throwable $e) {
            Log::warning('Overdue sync failed: '.$e->getMessage());
        }
    }

    // Asks Django whether a status change is allowed. Fails closed.
    public function validateTransition(int $taskId, string $newStatus, string $role): array
    {
        try {
            $response = $this->http()->post('/api/tasks/validate-transition', [
                'task_id' => $taskId,
                'new_status' => $newStatus,
                'role' => $role,
            ]);
        } catch (ConnectionException $e) {
            throw new ApiException('Overdue service is unavailable, try again shortly', 503);
        }

        if ($response->failed()) {
            throw new ApiException('Overdue service could not validate this change', 503);
        }

        return $response->json('data');
    }
}
