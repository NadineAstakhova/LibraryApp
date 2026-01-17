<?php

namespace App\Library\Infrastructure\Book\Database\Models;

use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'books';

    protected $fillable = [
        'title',
        'author',
        'isbn',
        'genre',
        'description',
        'total_copies',
        'available_copies',
        'publication_year',
    ];

    protected function casts(): array
    {
        return [
            'publication_year' => 'integer',
            'total_copies' => 'integer',
            'available_copies' => 'integer',
        ];
    }

    protected static function newFactory(): BookFactory
    {
        return BookFactory::new();
    }
}