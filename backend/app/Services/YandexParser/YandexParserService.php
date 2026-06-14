<?php

namespace App\Services\YandexParser;

use App\Rules\YandexOrganizationUrl;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class YandexParserService
{
    /**
     * @return array{company_info: array<string, mixed>, company_reviews: list<array<string, mixed>>}
     */
    public function parseOrganization(string $yandexOrgId): array
    {
        $baseUrl = rtrim((string) config('services.yandex_parser.url'), '/');
        $timeout = (int) config('services.yandex_parser.timeout', 300);

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get("{$baseUrl}/parse/{$yandexOrgId}");
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Сервис парсинга недоступен. Проверьте, что контейнер parser запущен.', 0, $exception);
        }

        if ($response->status() === 422) {
            return [
                'error' => $response->json('error') ?? 'Не удалось получить данные организации.',
            ];
        }

        if (! $response->successful()) {
            throw new RuntimeException('Сервис парсинга вернул ошибку: HTTP '.$response->status());
        }

        /** @var array{company_info?: array<string, mixed>, company_reviews?: list<array<string, mixed>>, error?: string} $data */
        $data = $response->json();

        if (isset($data['error'])) {
            return ['error' => (string) $data['error']];
        }

        return [
            'company_info' => $data['company_info'] ?? [],
            'company_reviews' => $data['company_reviews'] ?? [],
        ];
    }

    public function extractOrgIdFromUrl(string $url): ?string
    {
        return YandexOrganizationUrl::extractOrgId($url);
    }
}
