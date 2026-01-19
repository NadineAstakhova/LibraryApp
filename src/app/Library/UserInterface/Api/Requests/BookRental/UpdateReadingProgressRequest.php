<?php

namespace App\Library\UserInterface\Api\Requests\BookRental;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReadingProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'progress.required' => 'Reading progress is required',
            'progress.integer' => 'Reading progress must be an integer',
            'progress.min' => 'Reading progress must be at least 0',
            'progress.max' => 'Reading progress cannot exceed 100',
        ];
    }
}
