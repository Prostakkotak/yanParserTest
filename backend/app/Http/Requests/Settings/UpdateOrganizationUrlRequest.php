<?php

namespace App\Http\Requests\Settings;

use App\Rules\YandexOrganizationUrl;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'yandex_url' => ['required', 'string', 'url', 'max:2048', new YandexOrganizationUrl],
        ];
    }
}
