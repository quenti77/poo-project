<?php

use App\Enums\CommentStatus;
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
        $commentStatus = CommentStatus::values()
            ->map(static fn (int $k, string $v) => json_encode($v, JSON_THROW_ON_ERROR))
            ->join(', ');

        $statement = <<<EOS
        create table comments
        (
            id varchar(32) not null primary key,
            post_id varchar(32) not null,
            author_id varchar(32) not null,
            content longtext not null,
            status enum ({$commentStatus}) not null,
            created_at datetime not null,
            updated_at datetime not null,
            
            constraint comments_post_id_fk foreign key (post_id) references posts (id),
            constraint comments_users_id_fk foreign key (author_id) references users (id),
            index comments_status (status)
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
        $connection->request("drop table if exists `comments`");
    }
};
