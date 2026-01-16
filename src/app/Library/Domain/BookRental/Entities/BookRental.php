<?php

namespace App\Library\Domain\BookRental\Entities;

use App\Library\Domain\BookRental\ValueObjects\Status;
use Carbon\Carbon;

class BookRental
{
    private ?int $id;

    private int $bookId;

    private int $userId;

    private Carbon $rentedAt;

    private Carbon $returnedAt;

    private Status $status;

    public function __construct(?int $id, int $bookId, int $userId, Carbon $rentedAt, Status $status, Carbon $returnedAt = null)
    {
        $this->id = $id;
        $this->bookId = $bookId;
        $this->userId = $userId;
        $this->status = $status;
        $this->rentedAt = $rentedAt;
        $this->returnedAt = $returnedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBookId(): int
    {
        return $this->bookId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getRentedAt(): Carbon
    {
        return $this->rentedAt;
    }

    public function getReturnedAt(): ?Carbon
    {
        return $this->returnedAt;
    }

    public function setStatus(Status $status): void
    {
        $this->status = $status;
    }

    public function setReturnedAt(Carbon $returnedAt): void
    {
        $this->returnedAt = $returnedAt;
    }
}