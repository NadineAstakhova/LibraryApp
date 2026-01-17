<?php

namespace Database\Seeders;

use App\Library\Infrastructure\Book\Database\Models\Book;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $books = [
            [
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '978-0132350884',
                'genre' => 'Programming',
                'description' => 'A Handbook of Agile Software Craftsmanship',
                'total_copies' => 5,
                'available_copies' => 5,
                'publication_year' => 2008,
            ],
            [
                'title' => 'Harry Potter 1 and the Philosopher\'s Stone',
                'author' => 'J.K. Rowling ',
                'isbn' => '978-1526626585',
                'genre' => 'Fiction',
                'description' => 'An orphaned boy enrolls in a school of wizardry, where he learns the truth about himself, his family and the terrible evil that haunts the magical world.',
                'total_copies' => 10,
                'available_copies' => 10,
                'publication_year' => 2020,
            ],
        ];

        foreach ($books as $book) {
            Book::create($book);
        }

        // Create additional random books
        Book::factory(20)->create();
    }
}