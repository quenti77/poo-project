<?php

namespace App\Controllers;

use App\Repositories\Contracts\UsersRepositoryInterface;
use App\ValueObjects\Ulid;
use ReflectionException;
use Tuto\Http\Response\ViewResponse;

class UsersController
{
    public function __construct(private readonly UsersRepositoryInterface $usersRepository)
    {
    }

    /**
     * @throws ReflectionException
     */
    public function index(): ViewResponse
    {
        $users = $this->usersRepository->getActiveUsers();
        return view('users/index', ['users' => $users]);
    }

    /**
     * @throws ReflectionException
     */
    public function show(string $userId): ViewResponse
    {
        $user = $this->usersRepository->getById(new Ulid($userId));
        return view('users/show', ['user' => $user]);
    }
}