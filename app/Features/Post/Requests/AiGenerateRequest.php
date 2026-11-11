<?php

namespace App\Features\Post\Requests;

use App\Features\Post\Enums\PostCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AiGenerateRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'min:3', 'max:200'],
            'quantity' => ['required', 'integer', 'between:'.config('openai.min_quantity').','.config('openai.max_quantity')],
            'category' => ['nullable', Rule::enum(PostCategory::class)],
        ];
    }
}
