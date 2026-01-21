<?php

use Tuto\Routing\Router;

router()->group([
    'prefix' => '{lang?}/admin',
    'where' => ['lang' => '[a-z]{2}-[A-Z]{2}'],
    'name' => 'admin.',
    // 'middleware' => [],
], static function (Router $router) {

    $router->get('dashboard', static function (): \Tuto\Http\Responses\ViewResponse {
        $currentUser = new \App\Entities\User(
            \Tuto\Utils\ValueObject\Ulid::next(),
            new \App\Entities\Group(
                \Tuto\Utils\ValueObject\Ulid::next(),
                'admin',
                new \App\ValueObjects\Level(30),
                new DateTimeImmutable(),
                new DateTimeImmutable(),
            ),
            'admin',
            \App\ValueObjects\Email::fromString('admin@poo-project.local'),
            new DateTimeImmutable(),
            \App\ValueObjects\PasswordHashed::fromPlainText('password'),
            'token',
            new DateTimeImmutable(),
            new DateTimeImmutable(),
        );

        $posts = [];
        foreach (range(1, 3) as $item) {
            $posts[] = new \App\Entities\Post(
                \Tuto\Utils\ValueObject\Ulid::next(),
                $currentUser,
                'title ' . $item,
                new \App\ValueObjects\Slug('title-' . $item),
                'content of title ' . $item,
                new DateTimeImmutable(),
                new DateTimeImmutable(),
                new DateTimeImmutable(),
            );
        }

        $comments = [];
        foreach (range(1, 3) as $item) {
            $selectedPost = $posts[random_int(0, 2)];
            $comments[] = new \App\Entities\Comment(
                \Tuto\Utils\ValueObject\Ulid::next(),
                $selectedPost,
                $currentUser,
                'comment ' . $item . ' from post ' . $selectedPost->getId(),
                \App\Enums\CommentStatus::APPROVED,
                new DateTimeImmutable(),
                new DateTimeImmutable(),
            );
        }

        return view('pages/admin/dashboard.twig', [
            'title' => 'Dashboard',
            'user' => $currentUser,
            'stats' => [
                'posts' => 42,
                'comments' => 156,
                'users' => 28,
                'categories' => 8
            ],
            'recentPosts' => $posts,
            'recentComments' => $comments
        ]);
    });

});

