<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\Project;
use App\Models\User;
use App\Services\HoneycrispService;
use Illuminate\Support\Collection;

class PushHoursToHoneycrisp
{
    /**
     * @param  Collection<int, object>  $shifts
     * @return array{ok: bool, status?: ?int, message: string, data?: mixed}
     */
    public function __invoke(Project $project, Collection $shifts): array
    {
        if ($shifts->isEmpty()) {
            return ['ok' => true, 'message' => 'No unbilled shifts.'];
        }

        $honeycrispProjectId = trim((string) ($project->honeycrisp_project_id ?? ''));
        if ($honeycrispProjectId === '') {
            return ['ok' => false, 'message' => 'This project is not linked to a Honeycrisp project.'];
        }

        $productIds = User::query()
            ->whereIn('netid', $shifts->pluck('netid')->unique()->all())
            ->pluck('honeycrisp_product_id', 'netid');

        $missing = $shifts
            ->pluck('netid')
            ->unique()
            ->filter(fn ($netid) => trim((string) $productIds->get($netid)) === '');

        if ($missing->isNotEmpty()) {
            $names = User::query()
                ->whereIn('netid', $missing->all())
                ->orderBy('name')
                ->pluck('name')
                ->all();

            return [
                'ok' => false,
                'message' => 'These employees need a Honeycrisp product: '.implode(', ', $names).'.',
            ];
        }

        $minutesByProduct = [];
        foreach ($shifts as $shift) {
            $productId = (string) $productIds->get($shift->netid);
            $minutesByProduct[$productId] = ($minutesByProduct[$productId] ?? 0) + (int) ($shift->duration ?? 0);
        }

        $products = [];
        foreach ($minutesByProduct as $productId => $minutes) {
            $hours = round($minutes / 60, 2);
            if ($hours < 0.01) {
                return [
                    'ok' => false,
                    'message' => 'Hours for a product are below Honeycrisp’s 0.01 minimum.',
                ];
            }

            $products[] = [
                'id' => ctype_digit((string) $productId) ? (int) $productId : (string) $productId,
                'hours' => $hours,
            ];
        }

        return app(HoneycrispService::class)->pushHours($honeycrispProjectId, $products);
    }
}
