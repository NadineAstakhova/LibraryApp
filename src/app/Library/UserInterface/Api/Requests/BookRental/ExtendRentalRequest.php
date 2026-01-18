<?php

namespace App\Library\UserInterface\Api\Requests\BookRental;

use Illuminate\Foundation\Http\FormRequest;

class ExtendRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }
}