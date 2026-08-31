<?php

namespace App\Services;

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

    private function fetchList(string $resource): array
    {
        $url = rtrim((string) config('services.honeycrisp.url'), '/');
        $facilityId = config('services.honeycrisp.facility_id');
        $token = config('services.honeycrisp.token');

        if (! $url || ! $facilityId || ! $token) {
            return [];
        }

        $cacheKey = "honeycrisp.{$resource}";
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && false) {
            return $cached;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(10)
                ->get("{$url}/api/facilities/{$facilityId}/{$resource}");
        } catch (\Throwable $e) {
            Log::warning("Honeycrisp {$resource} fetch failed", ['message' => $e->getMessage()]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning("Honeycrisp {$resource} request unsuccessful", ['status' => $response->status()]);

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
}
