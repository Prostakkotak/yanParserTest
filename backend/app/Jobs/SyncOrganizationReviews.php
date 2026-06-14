<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Services\Organization\OrganizationSyncService;
use App\Services\YandexParser\YandexParserService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

class SyncOrganizationReviews implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $organizationId) {}

    public function handle(
        YandexParserService $parser,
        OrganizationSyncService $syncService,
    ): void {
        $organization = Organization::query()->find($this->organizationId);

        if (! $organization?->yandex_org_id) {
            return;
        }

        $organization->update([
            'sync_status' => Organization::SYNC_PROCESSING,
            'sync_error' => null,
        ]);

        try {
            $parsed = $parser->parseOrganization($organization->yandex_org_id);

            if (isset($parsed['error'])) {
                $organization->update([
                    'sync_status' => Organization::SYNC_FAILED,
                    'sync_error' => (string) $parsed['error'],
                ]);

                return;
            }

            $syncService->sync($organization, $parsed);

            $organization->update([
                'sync_status' => Organization::SYNC_COMPLETED,
                'sync_error' => null,
            ]);
        } catch (RuntimeException $exception) {
            $organization->update([
                'sync_status' => Organization::SYNC_FAILED,
                'sync_error' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            $organization->update([
                'sync_status' => Organization::SYNC_FAILED,
                'sync_error' => 'Не удалось загрузить отзывы.',
            ]);

            throw $exception;
        }
    }
}
