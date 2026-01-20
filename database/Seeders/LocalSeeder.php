<?php

namespace Database\Seeders;

use Database\Seeders\Local\CommentSeeder;
use Database\Seeders\Local\GroupSeeder;
use Database\Seeders\Local\PostSeeder;
use Database\Seeders\Local\UserSeeder;
use Throwable;
use Tuto\Database\Seeders\AbstractSeeder;

class LocalSeeder extends AbstractSeeder
{
    /**
     * @return void
     * @throws Throwable
     */
    public function run(): void
    {
        $this->call(GroupSeeder::class);
        $this->call(UserSeeder::class);

        $this->call(PostSeeder::class);
        $this->call(CommentSeeder::class);
    }
}
