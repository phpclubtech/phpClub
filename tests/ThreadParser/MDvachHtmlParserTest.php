<?php

declare(strict_types=1);

namespace Tests\ThreadParser;

use phpClub\Entity\Post;
use phpClub\ThreadParser\MDvachThreadParser;
use Tests\AbstractTestCase;

class MDvachHtmlParserTest extends AbstractTestCase
{
    /**
     * @var MDvachThreadParser
     */
    private $threadParser;

    public function setUp()
    {
        $this->threadParser = $this->getContainer()->get(MDvachThreadParser::class);
    }

    public function testExtractSinglePost()
    {
        $path = __DIR__ . '/../Fixtures/m2-ch/pr-thread-5-m2ch-googlecache-288635.html';
        $html = file_get_contents($path);

        $thread = $this->threadParser->extractThread($html);

        // OP post
        $post = $this->findPostById($thread->getPosts(), 288635);

        $this->assertContains('xnn2uE3AU', $post->getAuthor());
        $this->assertEquals('2013-06-18 06:19', $post->getDate()->format('Y-m-d h:i'));
        $this->assertContains('В этом ITT треде мы изучаем', $post->getText());
        $this->assertContains('Я хочу оформить код красиво', $post->getText());
        $this->assertContains('Клуб любителей изучать', $post->getTitle());
        $this->assertCount(1, $post->getFiles());

        // Last post
        $post2 = $this->findPostById($thread->getPosts(), 289749);
        $this->assertNotEmpty($post2);
    }

    private function findPostById(iterable $posts, int $id): ?Post
    {
        foreach ($posts as $post) {
            if ($post->getId() == $id) {
                return $post;
            }
        }

        return null;
    }

    public function testCanParseM2chThread()
    {
        $path = __DIR__ . '/../Fixtures/m2-ch/pr-thread-5-m2ch-googlecache-288635.html';
        $html = file_get_contents($path);

        $thread = $this->threadParser->extractThread($html);
        $posts = $thread->getPosts();

        $hasFiles = false;
        foreach ($posts as $post) {
            if (count($post->getFiles()) > 0) {
                $hasFiles = true;
            }
        }

        $this->assertTrue($hasFiles);
        $this->assertGreaterThan(100, count($posts));
    }
}
