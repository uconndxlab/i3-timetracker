<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HoneycrispService
{
    public function products(): array
    {
        $url = rtrim((string) config('services.honeycrisp.url'), '/');
        $facilityId = config('services.honeycrisp.facility_id');
        $token = config('services.honeycrisp.token');

        if (! $url || ! $facilityId || ! $token) {
            return [];
        }

        $cached = Cache::get('honeycrisp.products');
        if (is_array($cached)) {
            return $cached;
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(10)
                ->get("{$url}/api/facilities/{$facilityId}/products");
        } catch (\Throwable $e) {
            Log::warning('Honeycrisp products fetch failed', ['message' => $e->getMessage()]);

            return [];
        }

        if (! $response->successful()) {
            Log::warning('Honeycrisp products request unsuccessful', ['status' => $response->status()]);

            return [];
        }

        $products = $this->normalize($response->json());
        Cache::put('honeycrisp.products', $products, 300);

        return $products;
    }

    private function normalize(mixed $payload): array
    {
        $items = is_array($payload) && array_key_exists('data', $payload)
            ? $payload['data']
            : $payload;

        if (! is_array($items)) {
            return [];
        }

        $list = array_is_list($items) ? $items : [$items];
        $products = [];

        foreach ($list as $item) {
            if (! is_array($item)) {
                continue;
            }

            $id = $item['id'] ?? null;
            if ($id === null || $id === '') {
                continue;
            }

            $products[] = [
                'id' => (string) $id,
                'name' => (string) ($item['name'] ?? $item['label'] ?? $id),
            ];
        }

        return $products;
    }
}
