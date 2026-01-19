<?php

namespace App\Library\UserInterface\Api\Requests\Book;

use App\Library\Domain\Book\ValueObjects\BookSearchCriteria;
use Illuminate\Foundation\Http\FormRequest;

class SearchBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowedSortFields = implode(',', BookSearchCriteria::ALLOWED_SORT_FIELDS);
        
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'author' => ['nullable', 'string', 'max:255'],
            'genre' => ['nullable', 'string', 'max:100'],
            'available_only' => ['nullable', 'boolean'],
            'sort_by' => ['nullable', 'string', 'in:' . $allowedSortFields],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'sort_by.in' => 'The sort_by field must be one of: ' . implode(', ', BookSearchCriteria::ALLOWED_SORT_FIELDS),
        ];
    }
}