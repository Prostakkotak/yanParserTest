<?php

namespace App\Http\Requests\Settings;

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
            // TODO: add custom rule for Yandex Maps organization URL
            'yandex_url' => ['required', 'string', 'url', 'max:2048'],
        ];
    }
}
