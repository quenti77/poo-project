<?php

namespace Database\Seeders\Local;

use InvalidArgumentException;
use Throwable;
use Tuto\Database\Query\QueryMaker;
use Tuto\Database\Seeders\AbstractSeeder;
use Tuto\Utils\Str;
use Tuto\Utils\ValueObject\Ulid;

class PostSeeder extends AbstractSeeder
{
    /**
     * @return void
     * @throws Throwable
     */
    public function run(): void
    {
        $now = $this->currentTime->now();

        foreach (range(1, 10) as $item) {
            $this->createPost([
                'id' => Ulid::next(),
                'author_id' => UserSeeder::MEMBER_ID,
                'title' => "Titre {$item}",
                'content' => "Contenu pour l'article {$item}",
                'published_at' => random_int(1, 5) === 3 ? null : $now,
            ]);
        }
    }

    /**
     * @param array $data
     * @return void
     * @throws InvalidArgumentException
     */
    private function createPost(array $data): void
    {
        $id = $data['id'] ?? throw new InvalidArgumentException("Missing id");
        $authorId = $data['author_id'] ?? throw new InvalidArgumentException("Missing author_id");

        $now = $this->currentTime->now();

        $title = $data['title'] ?? 'Titre ' . uniqid('', true);
        $slug = Str::slug($data['slug'] ?? $title);

        $data = [
            'id' => $id,
            'author_id' => $authorId,
            'title' => $title,
            'slug' => $slug,
            'content' => 'Cet article de test présente brièvement le contenu, sert aux essais techniques et permet de valider l’affichage général du site.',
            'published_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
            ...$data,
        ];

        QueryMaker::insert('posts')
            ->values($data)
            ->render()
            ->makeRequest($this->connection);
    }

}