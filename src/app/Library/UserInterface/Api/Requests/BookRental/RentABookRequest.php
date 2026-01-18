<?php

namespace App\Library\UserInterface\Api\Requests\BookRental;

use Illuminate\Foundation\Http\FormRequest;

class RentABookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', 'exists:books,id'],
        ];
    }
}