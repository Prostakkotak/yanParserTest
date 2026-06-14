<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateOrganizationUrlRequest;
use App\Models\Organization;
use App\Services\Organization\OrganizationSyncService;
use App\Services\YandexParser\YandexParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SettingsController extends Controller
{
    public function __construct(
        private readonly YandexParserService $parser,
        private readonly OrganizationSyncService $syncService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $organization = Organization::query()
            ->where('user_id', $request->user()->id)
            ->first();

        return response()->json([
            'organization' => $organization,
        ]);
    }

    public function update(UpdateOrganizationUrlRequest $request): JsonResponse
    {
        $yandexUrl = $request->validated('yandex_url');
        $orgId = $this->parser->extractOrgIdFromUrl($yandexUrl);

        try {
            $parsed = $this->parser->parseOrganization($orgId);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 503);
        }

        if (isset($parsed['error'])) {
            return response()->json([
                'message' => $parsed['error'],
            ], 422);
        }

        $organization = Organization::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'yandex_url' => $yandexUrl,
                'yandex_org_id' => $orgId,
            ]
        );

        $organization = $this->syncService->sync($organization, $parsed);

        return response()->json([
            'message' => 'Настройки сохранены. Данные организации обновлены.',
            'organization' => $organization,
            'reviews_synced' => $organization->reviews_count,
        ]);
    }
}
