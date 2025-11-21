<?php

use App\Repositories\Contracts\UsersRepositoryInterface;
use App\Repositories\Database\DbUsersRepository;
use Tuto\Container\Items\DependencyItem;

return [
    DependencyItem::interface(UsersRepositoryInterface::class, DbUsersRepository::class),
];
