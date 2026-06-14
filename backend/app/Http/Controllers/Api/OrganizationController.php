<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $organization = Organization::query()
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $organization) {
            return response()->json(['message' => 'Организация не настроена.'], 404);
        }

        return response()->json([
            'organization' => $organization,
        ]);
    }

    public function reviews(Request $request): JsonResponse
    {
        $organization = Organization::query()
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $organization) {
            return response()->json(['message' => 'Организация не настроена.'], 404);
        }

        $perPage = min(max((int) $request->query('per_page', 10), 1), 50);

        $reviews = Review::query()
            ->where('organization_id', $organization->id)
            ->orderByDesc('reviewed_at')
            ->paginate($perPage);

        return response()->json([
            'organization' => [
                'name' => $organization->name,
                'avg_rating' => $organization->avg_rating,
                'ratings_count' => $organization->ratings_count,
                'reviews_count' => $organization->reviews_count,
            ],
            'reviews' => $reviews,
        ]);
    }
}
