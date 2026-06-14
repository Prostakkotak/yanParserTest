<?php

namespace App\Services\Organization;

use App\Models\Organization;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrganizationSyncService
{
    /**
     * @param  array{company_info?: array<string, mixed>, company_reviews?: list<array<string, mixed>>}  $data
     */
    public function sync(Organization $organization, array $data): Organization
    {
        $info = $data['company_info'] ?? [];
        $reviews = $data['company_reviews'] ?? [];

        return DB::transaction(function () use ($organization, $info, $reviews) {
            $organization->update([
                'name' => $info['name'] ?? null,
                'avg_rating' => isset($info['rating']) ? (float) $info['rating'] : null,
                'ratings_count' => isset($info['count_rating']) ? (int) $info['count_rating'] : null,
                'reviews_count' => count($reviews),
                'last_synced_at' => now(),
            ]);

            $organization->reviews()->delete();

            foreach ($reviews as $review) {
                Review::query()->create([
                    'organization_id' => $organization->id,
                    'external_id' => $this->makeExternalId($organization->yandex_org_id, $review),
                    'author' => $review['name'] ?? null,
                    'text' => $review['text'] ?? null,
                    'rating' => isset($review['stars']) ? (int) round((float) $review['stars']) : null,
                    'reviewed_at' => $this->parseReviewDate($review['date'] ?? null),
                ]);
            }

            return $organization->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $review
     */
    private function makeExternalId(?string $orgId, array $review): string
    {
        $payload = implode('|', [
            $orgId ?? '',
            $review['name'] ?? '',
            (string) ($review['date'] ?? ''),
            mb_substr((string) ($review['text'] ?? ''), 0, 120),
        ]);

        return hash('sha256', $payload);
    }

    private function parseReviewDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
