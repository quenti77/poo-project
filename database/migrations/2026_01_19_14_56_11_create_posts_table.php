<?php

use Tuto\Database\ConnectionInterface;
use Tuto\Database\Migrations\MigrationInterface;

return new class implements MigrationInterface
{
    /**
     * @param ConnectionInterface $connection
     * @return void
     */
    public function up(ConnectionInterface $connection): void
    {
        $statement = <<<EOS
        create table posts
        (
            id varchar(32) not null primary key,
            author_id varchar(32) not null,
            title varchar(255) not null,
            slug varchar(255) not null,
            content longtext not null,
            published_at datetime null,
            created_at datetime not null,
            updated_at datetime not null,
            
            constraint posts_users_id_fk foreign key (author_id) references users (id),
            constraint posts_slug_uindex unique (slug),
            index posts_published_at (published_at)
        );
        EOS;
        $connection->request($statement);
    }

    /**
     * @param ConnectionInterface $connection
     * @return void
     */
    public function down(ConnectionInterface $connection): void
    {
        $connection->request("drop table if exists `posts`");
    }
};
