<?php

namespace App\Services\YandexParser;

/**
 * Stub for future integration with python/services/yandex_reviews_parser.
 */
class YandexParserService
{
    /**
     * @return array{company_info: array<string, mixed>, company_reviews: list<array<string, mixed>>}
     */
    public function parseOrganization(string $yandexOrgId): array
    {
        // TODO: invoke Python parser or port logic to PHP
        return [
            'company_info' => [],
            'company_reviews' => [],
        ];
    }

    public function extractOrgIdFromUrl(string $url): ?string
    {
        // TODO: parse Yandex Maps organization URL
        return null;
    }
}
