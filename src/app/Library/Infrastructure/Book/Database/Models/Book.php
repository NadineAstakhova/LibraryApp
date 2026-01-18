<?php

namespace App\Library\Infrastructure\Book\Database\Models;

use App\Library\Infrastructure\BookRental\Database\Models\BookRental;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $title
 * @property string $author
 * @property string $isbn
 * @property string $genre
 * @property string|null $description
 * @property int $total_copies
 * @property int $available_copies
 * @property int|null $publication_year
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder|Book find(int $id)
 */
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

    public function bookRentals(): HasMany
    {
        return $this->hasMany(BookRental::class);
    }

    protected static function newFactory(): BookFactory
    {
        return BookFactory::new();
    }
}