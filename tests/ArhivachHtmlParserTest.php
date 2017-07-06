<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use phpClub\ThreadParser\Thread\ArhivachThread;
use phpClub\ThreadParser\{DateConverter, ThreadHtmlParser};

class ArhivachHtmlParserTest extends TestCase
{
    /**
     * @var ThreadHtmlParser
     */
    private $threadParser;

    public function setUp()
    {
        $this->threadParser = new ThreadHtmlParser(new DateConverter(), new ArhivachThread());
    }

    public function testThread83()
    {
        $posts = $this->threadParser->getPosts(file_get_contents(__DIR__ . '/arhivach_fixtures/arhivach_thread_83.html'));
        $post  = $posts[16];
        $this->assertEquals('Аноним', $post->author);
        $this->assertEquals('24/11/16 23:34:24', $post->date->format('d/m/y H:i:s'));
        $this->assertEquals('881710', $post->id);
        $this->assertContains('Помогите с циклами, ребята.', $post->text);
        $this->assertEquals('', $post->title);
        $this->assertCount(0, $post->files);

        $post = $posts[452];
        $this->assertContains('Лучше всего почитать у Фаулера', $post->text);
        $this->assertEquals('Аноним', $post->author);
        $this->assertEquals('', $post->title);
        $this->assertCount(0, $post->files);

        $post = end($posts);
        $this->assertEquals('Аноним', $post->author);
        $this->assertContains('Проверил файлообменник и написал в новом треде', $post->text);
        $this->assertEquals('github.com/fidnex/filehost', $post->title);
        $this->assertCount(0, $post->files);

        $post = $posts[92];
        $this->assertCount(3, $post->files);
    }

    /**
     * @dataProvider provideThreadsHtml
     */
    public function testGetPosts($pathToThreadHtml)
    {
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToThreadHtml));
        $this->assertGreaterThan(100, count($threadArray));
        $this->assertNotEmpty($threadArray[0]->author);
        $this->assertNotEmpty($threadArray[0]->id);
        $this->assertNotEmpty($threadArray[0]->text);
    }

    public function provideThreadsHtml()
    {
        return [
            [__DIR__ . '/arhivach_fixtures/arhivach_thread_83.html'],
        ];
    }

    public function testFiles()
    {
        $pathToHtml  = __DIR__ . '/arhivach_fixtures/arhivach_thread_83.html';
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToHtml));
        $files       = $threadArray[0]->files;

        $this->assertCount(4, $files);

        // Image 1
        $this->assertEquals('https://arhivach.org/storage2/7/11/71139561f22f2ea253a15d6f442457f6.png', $files[0]->fullName);
        $this->assertEquals(500, $files[0]->width);
        $this->assertEquals(500, $files[0]->height);
        $this->assertEquals('https://arhivach.org/storage2/t/71139561f22f2ea253a15d6f442457f6.png', $files[0]->thumbName);

        // Image 2
        $this->assertEquals('https://arhivach.org/storage2/8/63/8631bf83d67be92f483f5cff54c2ed5b.jpg', $files[1]->fullName);
        $this->assertEquals(1024, $files[1]->width);
        $this->assertEquals(683, $files[1]->height);
        $this->assertEquals('https://arhivach.org/storage2/t/8631bf83d67be92f483f5cff54c2ed5b.jpg', $files[1]->thumbName);

        // Image 3
        $this->assertEquals('https://arhivach.org/storage2/1/b5/1b529b966894cfc14379bc5a0fea5455.png', $files[2]->fullName);
        $this->assertEquals(853, $files[2]->width);
        $this->assertEquals(480, $files[2]->height);
        $this->assertEquals('https://arhivach.org/storage2/t/1b529b966894cfc14379bc5a0fea5455.png', $files[2]->thumbName);

        $this->assertCount(1, $threadArray[1]->files);
        $this->assertCount(0, $threadArray[2]->files);
    }
}
