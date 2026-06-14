<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class YandexOrganizationUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::extractOrgId($value)) {
            $fail('Укажите ссылку на карточку организации в Яндекс.Картах (yandex.ru или yandex.com).');
        }
    }

    public static function extractOrgId(string $url): ?string
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || ! preg_match('/(^|\.)yandex\.(ru|com)$/i', $host)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || ! preg_match('#/maps/org/(?:[^/]+/)?(\d+)#', $path, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
