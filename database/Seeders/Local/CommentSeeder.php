<?php

namespace Database\Seeders\Local;

use App\Enums\CommentStatus;
use InvalidArgumentException;
use Throwable;
use Tuto\Database\Query\QueryMaker;
use Tuto\Database\Seeders\AbstractSeeder;
use Tuto\Utils\ValueObject\Ulid;

class CommentSeeder extends AbstractSeeder
{
    /**
     * @return void
     * @throws Throwable
     */
    public function run(): void
    {
        foreach ($this->getPosts() as $post) {
            $numberComments = random_int(10, 30);
            foreach (range(1, $numberComments) as $item) {
                $this->createComment([
                    'id' => Ulid::next(),
                    'post_id' => $post['id'],
                    'author_id' => UserSeeder::MEMBER_ID,
                    'content' => "Commentaire {$item} pour l'article {$post['id']}",
                ]);
            }
        }
    }

    /**
     * @return array
     */
    private function getPosts(): array
    {
        return QueryMaker::select('id')->from('posts')->render()->makeRequest($this->connection)->fetchAll();
    }

    /**
     * @param array $data
     * @return void
     * @throws InvalidArgumentException
     */
    private function createComment(array $data): void
    {
        $id = $data['id'] ?? throw new InvalidArgumentException("Missing id");
        $postId = $data['post_id'] ?? throw new InvalidArgumentException("Missing post_id");
        $authorId = $data['author_id'] ?? throw new InvalidArgumentException("Missing author_id");

        $now = $this->currentTime->now();

        $data = [
            'id' => $id,
            'post_id' => $postId,
            'author_id' => $authorId,
            'content' => 'Commentaire de test #' . uniqid('', true),
            'status' => CommentStatus::cases()[array_rand(CommentStatus::cases())],
            'created_at' => $now,
            'updated_at' => $now,
            ...$data,
        ];

        QueryMaker::insert('comments')
            ->values($data)
            ->render()
            ->makeRequest($this->connection);
    }

}