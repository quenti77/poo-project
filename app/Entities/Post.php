<?php

namespace App\Entities;

use App\Traits\EntityTimestampsTrait;
use App\ValueObjects\Slug;
use DateTimeImmutable;
use Tuto\Utils\ValueObject\Ulid;

class Post
{
    use EntityTimestampsTrait;

    /**
     * @param Ulid $id
     * @param User $author
     * @param string $title
     * @param Slug $slug
     * @param string $content
     * @param DateTimeImmutable|null $publishedAt
     * @param DateTimeImmutable $createdAt
     * @param DateTimeImmutable $updatedAt
     */
    public function __construct(
        private readonly Ulid $id,
        private User $author,
        private string $title,
        private Slug $slug,
        private string $content,
        private DateTimeImmutable|null $publishedAt,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ) {
        $this->createdAt = $createdAt;
        $this->update($updatedAt);
    }

    /**
     * @return Ulid
     */
    public function getId(): Ulid
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getAuthor(): User
    {
        return $this->author;
    }

    /**
     * @param User $author
     * @return void
     */
    public function transferTo(User $author): void
    {
        $this->author = $author;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return void
     */
    public function changeTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return Slug
     */
    public function getSlug(): Slug
    {
        return $this->slug;
    }

    /**
     * @param Slug|string $slug
     * @return void
     */
    public function changeSlug(Slug|string $slug): void
    {
        if (is_string($slug)) {
            $slug = new Slug($slug);
        }
        $this->slug = $slug;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return void
     */
    public function updateContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getPublishedAt(): DateTimeImmutable|null
    {
        return $this->publishedAt;
    }

    /**
     * @param DateTimeImmutable $current
     * @return void
     */
    public function publish(DateTimeImmutable $current): void
    {
        $this->publishedAt = $current;
    }

    /**
     * @return void
     */
    public function unpublish(): void
    {
        $this->publishedAt = null;
    }
}