<?php

namespace App\Entities;

use App\Enums\CommentStatus;
use App\Traits\EntityTimestampsTrait;
use DateTimeImmutable;
use Tuto\Utils\ValueObject\Ulid;

class Comment
{
    use EntityTimestampsTrait;

    /**
     * @param Ulid $id
     * @param Post $post
     * @param User $author
     * @param string $content
     * @param CommentStatus $status
     * @param DateTimeImmutable $createdAt
     * @param DateTimeImmutable $updatedAt
     */
    public function __construct(
        private readonly Ulid $id,
        private Post $post,
        private User $author,
        private string $content,
        private CommentStatus $status,
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
     * @return Post
     */
    public function getPost(): Post
    {
        return $this->post;
    }

    /**
     * @param Post $post
     * @return void
     */
    public function moveTo(Post $post): void
    {
        $this->post = $post;
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
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return void
     */
    public function changeContent(string $content): void
    {
        $this->content = $content;
        $this->status = CommentStatus::PENDING;
    }

    /**
     * @return CommentStatus
     */
    public function getStatus(): CommentStatus
    {
        return $this->status;
    }

    /**
     * @return void
     */
    public function approve(): void
    {
        $this->status = CommentStatus::APPROVED;
    }

    /**
     * @return void
     */
    public function reject(): void
    {
        $this->status = CommentStatus::REJECTED;
    }
}