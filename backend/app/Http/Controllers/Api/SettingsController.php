<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateOrganizationUrlRequest;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
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
        // TODO: validate Yandex Maps URL, extract org ID, fetch data via YandexParserService
        $organization = Organization::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'yandex_url' => $request->validated('yandex_url'),
                'yandex_org_id' => null,
                'name' => null,
                'avg_rating' => null,
                'ratings_count' => null,
                'reviews_count' => null,
                'last_synced_at' => null,
            ]
        );

        return response()->json([
            'message' => 'Настройки сохранены.',
            'organization' => $organization,
        ]);
    }
}
