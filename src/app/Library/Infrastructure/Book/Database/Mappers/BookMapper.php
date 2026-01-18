<?php

namespace App\Library\Infrastructure\Book\Database\Mappers;

use App\Library\Domain\Book\Entities\Book as BookEntity;
use App\Library\Domain\Book\ValueObjects\ISBN;
use App\Library\Infrastructure\Book\Database\Models\Book as BookEloquentModel;

class BookMapper
{
    public function fromEloquentModelToEntity(BookEloquentModel $book): BookEntity
    {
        return new BookEntity(
            id: $book->id,
            title: $book->title,
            author: $book->author,
            isbn: new ISBN($book->isbn),
            genre: $book->genre,
            description: $book->description,
            totalCopies: $book->total_copies,
            availableCopies: $book->available_copies,
            publicationYear: $book->publication_year,
        );
    }

    public static function toArray(BookEntity $entity): array
    {
        return $entity->toArray();
    }
}