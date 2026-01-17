<?php

namespace Database\Factories;

use App\Library\Infrastructure\Book\Database\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        $totalCopies = fake()->numberBetween(1, 10);

        return [
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'isbn' => fake()->isbn13(),
            'genre' => fake()->randomElement(['Fiction', 'Non-Fiction', 'Science', 'History', 'Technology', 'Programming']),
            'description' => fake()->paragraph(),
            'total_copies' => $totalCopies,
            'available_copies' => $totalCopies,
            'publication_year' => fake()->numberBetween(1950, 2024),
        ];
    }
}