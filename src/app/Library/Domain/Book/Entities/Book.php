<?php

namespace App\Library\Domain\Book\Entities;

use App\Library\Domain\Book\ValueObjects\ISBN;

class Book
{
    public function __construct(
        private ?int $id,
        private string $title,
        private string $author, //for now only one author. should be separate entity
        private ISBN $isbn,
        private string $genre,//for now only one genre. should be separate entity
        private ?string $description,
        private int $totalCopies,
        private int $availableCopies,
        private ?int $publicationYear,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getIsbn(): ISBN
    {
        return $this->isbn;
    }

    public function getGenre(): string
    {
        return $this->genre;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getTotalCopies(): int
    {
        return $this->totalCopies;
    }

    public function getAvailableCopies(): int
    {
        return $this->availableCopies;
    }

    public function getPublicationYear(): ?int
    {
        return $this->publicationYear;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function isAvailable(): bool
    {
        return $this->availableCopies > 0;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function setIsbn(ISBN $isbn): void
    {
        $this->isbn = $isbn;
    }

    public function setGenre(string $genre): void
    {
        $this->genre = $genre;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setPublicationYear(?int $publicationYear): void
    {
        $this->publicationYear = $publicationYear;
    }

    public function setAvailableCopies(int $availableCopies): void
    {
        $this->availableCopies = $availableCopies;
    }

    public function setTotalCopies(int $totalCopies): void
    {
        $this->totalCopies = $totalCopies;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn->getValue(),
            'genre' => $this->genre,
            'description' => $this->description,
            'total_copies' => $this->totalCopies,
            'available_copies' => $this->availableCopies,
            'publication_year' => $this->publicationYear,
        ];
    }
}