<?php

namespace App\Library\UserInterface\Api\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bookId = $this->route('id');
        
        return [
            'version' => ['required', 'integer', 'min:1'],
            'title' => ['sometimes', 'string', 'max:255'],
            'author' => ['sometimes', 'string', 'max:255'],
            'isbn' => [
                'sometimes',
                'string',
                'max:20',
                Rule::unique('books', 'isbn')->ignore($bookId),
            ],
            'genre' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'total_copies' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'publication_year' => ['nullable', 'integer', 'min:1000', 'max:' . (date('Y') + 1)],
        ];
    }

    public function messages(): array
    {
        return [
            'version.required' => 'Version is required for optimistic locking.',
            'isbn.unique' => 'A book with this ISBN already exists.',
            'publication_year.max' => 'Publication year cannot be in the future.',
        ];
    }
}
