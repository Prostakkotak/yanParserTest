<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateOrganizationUrlRequest;
use App\Jobs\SyncOrganizationReviews;
use App\Models\Organization;
use App\Services\YandexParser\YandexParserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private readonly YandexParserService $parser,
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

        $organization = Organization::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'yandex_url' => $yandexUrl,
                'yandex_org_id' => $orgId,
                'sync_status' => Organization::SYNC_PENDING,
                'sync_error' => null,
            ]
        );

        SyncOrganizationReviews::dispatch($organization->id);

        return response()->json([
            'message' => 'Настройки сохранены. Загрузка отзывов запущена.',
            'organization' => $organization->fresh(),
        ]);
    }
}
