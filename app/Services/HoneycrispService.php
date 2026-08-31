<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HoneycrispService
{
    public function products(): array
    {
        return $this->fetchList('products');
    }

    public function projects(): array
    {
        return $this->fetchList('projects');
    }

    /**
     * @param  list<array{id: int|string, hours: float}>  $products
     * @return array{ok: bool, status: ?int, message: string, data?: mixed}
     */
    public function pushHours(string $honeycrispProjectId, array $products): array
    {
        $url = $this->apiUrl("projects/{$honeycrispProjectId}/hours");
        $token = $this->token();

        if (! $url || ! $token) {
            return $this->failure('Honeycrisp is not configured.');
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->asJson()
                ->timeout(10)
                ->post($url, ['products' => $products]);
        } catch (ConnectionException $e) {
            Log::warning('Honeycrisp hours request timed out', ['message' => $e->getMessage()]);

            return $this->failure(
                'Honeycrisp request timed out. Hours may or may not have been saved — do not retry without checking.',
            );
        } catch (\Throwable $e) {
            Log::warning('Honeycrisp hours request failed', ['message' => $e->getMessage()]);

            return $this->failure('Honeycrisp request failed.');
        }

        if ($response->status() === 200) {
            $payload = $response->json();

            return [
                'ok' => true,
                'status' => 200,
                'message' => 'Hours sent to Honeycrisp.',
                'data' => is_array($payload) ? ($payload['data'] ?? $payload) : $payload,
            ];
        }

        $message = $this->messageFromResponse($response->json())
            ?? 'Honeycrisp request failed.';

        Log::warning('Honeycrisp hours request unsuccessful', [
            'status' => $response->status(),
            'message' => $message,
        ]);

        return $this->failure($message, $response->status());
    }

    private function fetchList(string $resource): array
    {
        $facilityId = config('services.honeycrisp.facility_id');
        $url = $this->apiUrl("facilities/{$facilityId}/{$resource}");
        $token = $this->token();

        if (! $url || ! $facilityId || ! $token) {
            return [];
        }

        $cacheKey = "honeycrisp.{$resource}";
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(10)
                ->get($url);
        } catch (\Throwable $e) {
            Log::warning("Honeycrisp {$resource} fetch failed", ['message' => $e->getMessage()]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning("Honeycrisp {$resource} request unsuccessful", [
                'status' => $response->status(),
                'message' => $this->messageFromResponse($response->json()),
            ]);

            return [];
        }

        $items = $this->normalize($response->json());
        Cache::put($cacheKey, $items, 300);

        return $items;
    }

    private function normalize(mixed $payload): array
    {
        $items = is_array($payload) ? ($payload['data'] ?? []) : [];

        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = $item['id'] ?? null;
            if ($id === null || $id === '') {
                continue;
            }

            $normalized[] = [
                'id' => (string) $id,
                'name' => (string) ($item['name'] ?? $id),
            ];
        }

        return $normalized;
    }

    private function apiUrl(string $path): ?string
    {
        $base = rtrim((string) config('services.honeycrisp.url'), '/');
        if ($base === '') {
            return null;
        }

        if (! str_ends_with(strtolower($base), '/api')) {
            $base .= '/api';
        }

        return $base.'/'.ltrim($path, '/');
    }

    private function token(): ?string
    {
        $token = config('services.honeycrisp.token');

        return $token ? (string) $token : null;
    }

    private function messageFromResponse(mixed $payload): ?string
    {
        if (! is_array($payload)) {
            return null;
        }

        if (isset($payload['message']) && is_string($payload['message']) && $payload['message'] !== '') {
            return $payload['message'];
        }

        if (isset($payload['errors']) && is_array($payload['errors'])) {
            foreach ($payload['errors'] as $messages) {
                $first = is_array($messages) ? ($messages[0] ?? null) : $messages;
                if (is_string($first) && $first !== '') {
                    return $first;
                }
            }
        }

        return null;
    }

    /**
     * @return array{ok: bool, status: ?int, message: string}
     */
    private function failure(string $message, ?int $status = null): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
        ];
    }
}
