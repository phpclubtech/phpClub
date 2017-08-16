<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\DTO;

class Post
{
    public $id;
    public $title;
    public $author;
    public $text;
    public $files;
    public $date;

    public function __construct(
        int $id,
        string $title,
        string $author,
        \DateTimeImmutable $date,
        string $text,
        array $files = []
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->author = $author;
        $this->date = $date;
        $this->text = $text;
        $this->files = $files;
    }
}
