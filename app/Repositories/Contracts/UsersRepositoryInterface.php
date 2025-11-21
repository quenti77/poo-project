<?php

namespace App\Repositories\Contracts;

use App\Entities\User;
use App\ValueObjects\Ulid;

interface UsersRepositoryInterface
{
    public function getActiveUsers(): array;

    public function getById(Ulid $userId): User;
}