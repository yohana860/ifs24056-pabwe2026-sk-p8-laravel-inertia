<?php

namespace App\Features\Post\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportPostRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'extensions:xlsx,xls', 'max:2048'],
        ];
    }
}
