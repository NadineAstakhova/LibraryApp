<?php

namespace Database\Factories;

use App\Library\Domain\BookRental\ValueObjects\Status;
use App\Library\Infrastructure\Book\Database\Models\Book;
use App\Library\Infrastructure\BookRental\Database\Models\BookRental;
use App\Library\Infrastructure\User\Database\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookRentalFactory extends Factory
{
    protected $model = BookRental::class;

    public function definition(): array
    {
        $rentedAt = fake()->dateTimeBetween('-30 days', 'now');
        $dueDate = (clone $rentedAt)->modify('+14 days');

        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'rented_at' => $rentedAt,
            'due_date' => $dueDate,
            'returned_at' => null,
            'status' => Status::ACTIVE,
            'reading_progress' => fake()->numberBetween(0, 100),
            'extension_count' => 0,
        ];
    }

    /**
     * Indicate that the rental is active (with future due date).
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Status::ACTIVE,
            'returned_at' => null,
            'rented_at' => now(),
            'due_date' => now()->addDays(14),
        ]);
    }

    /**
     * Indicate that the rental is completed (returned).
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Status::RETURNED,
            'returned_at' => now(),
            'reading_progress' => 100,
        ]);
    }

    /**
     * Indicate that the rental is overdue.
     */
    public function overdue(): static
    {
        $rentedAt = fake()->dateTimeBetween('-60 days', '-30 days');
        $dueDate = (clone $rentedAt)->modify('+14 days');

        return $this->state(fn (array $attributes) => [
            'status' => Status::OVERDUE,
            'rented_at' => $rentedAt,
            'due_date' => $dueDate,
            'returned_at' => null,
        ]);
    }

    /**
     * Set a specific user for the rental.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Set a specific book for the rental.
     */
    public function forBook(Book $book): static
    {
        return $this->state(fn (array $attributes) => [
            'book_id' => $book->id,
        ]);
    }

    /**
     * Set the reading progress.
     */
    public function withProgress(int $progress): static
    {
        return $this->state(fn (array $attributes) => [
            'reading_progress' => $progress,
        ]);
    }

    /**
     * Set the extension count.
     */
    public function withExtensions(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'extension_count' => $count,
        ]);
    }
}
