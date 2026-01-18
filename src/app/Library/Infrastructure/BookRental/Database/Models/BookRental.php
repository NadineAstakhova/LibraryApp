<?php

namespace App\Library\Infrastructure\BookRental\Database\Models;

use App\Library\Infrastructure\Book\Database\Models\Book;
use App\Library\Infrastructure\User\Database\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $book_id
 * @property \Illuminate\Support\Carbon|null $rented_at
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property \Illuminate\Support\Carbon|null $returned_at
 * @property string $status
 * @property int $reading_progress
 * @property int $extension_count
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 *
 */
class BookRental extends Model
{
    protected $table = 'book_rents';

    protected $fillable = [
        'user_id',
        'book_id',
        'rented_at',
        'due_date',
        'returned_at',
        'status',
        'reading_progress',
        'extension_count',
    ];

    protected function casts(): array
    {
        return [
            'rented_at' => 'datetime',
            'due_date' => 'datetime',
            'returned_at' => 'datetime',
            'reading_progress' => 'integer',
            'extension_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}