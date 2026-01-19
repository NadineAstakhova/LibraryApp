<?php

namespace App\Library\UserInterface\Api\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'string', 'max:20', 'unique:books,isbn'],
            'genre' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'total_copies' => ['required', 'integer', 'min:1', 'max:1000'],
            'publication_year' => ['nullable', 'integer', 'min:1000', 'max:' . (date('Y') + 1)],
        ];
    }

    public function messages(): array
    {
        return [
            'isbn.unique' => 'A book with this ISBN already exists.',
            'publication_year.max' => 'Publication year cannot be in the future.',
        ];
    }
}
