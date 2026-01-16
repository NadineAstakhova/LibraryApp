<?php

namespace App\Library\Domain\Book\Entities;

class Book
{
    private ?int $id;

    private string $name;

    private string $author; //for now, we don't have a separate Author entity, but better to have it

    private string $genre; //for now, we don't have a separate Genre entity, but better to have it

    private int $numberInLibrary;

    public function __construct(?int $id, string $name, string $author, string $genre, int $numberInLibrary)
    {
        $this->id = $id;
        $this->name = $name;
        $this->author = $author;
        $this->genre = $genre;
        $this->numberInLibrary = $numberInLibrary;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getGenre(): string
    {
        return $this->genre;
    }

    public function getNumberInLibrary(): int
    {
        return $this->numberInLibrary;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setAuthor(string $author): void
    {
        $this->author = $author;
    }

    public function setGenre(string $genre): void
    {
        $this->genre = $genre;
    }

    public function setNumberInLibrary(int $numberInLibrary): void
    {
        $this->numberInLibrary = $numberInLibrary;
    }
}