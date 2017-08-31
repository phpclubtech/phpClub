<?php

declare(strict_types=1);

namespace Tests\ThreadParser;

use phpClub\Entity\{Post, File};
use phpClub\ThreadParser\Thread\DvachThread;
use phpClub\ThreadParser\Helper\DateConverter;
use phpClub\ThreadParser\ThreadProvider\ThreadHtmlParser;
use PHPUnit\Framework\TestCase;
use Zend\EventManager\EventManager;

class DvachHtmlParserTest extends TestCase
{
    /**
     * @var ThreadHtmlParser
     */
    private $threadParser;

    public function setUp()
    {
        $this->threadParser = new ThreadHtmlParser(new EventManager(), new DateConverter(), new DvachThread());
    }

    public function testGetPost()
    {
        $thread = $this->threadParser->extractThread(file_get_contents(__DIR__ . '/dvach_fixtures/posts/post-thread-17.html'));
        $posts = $thread->getPosts();
        $post = $posts[0];
        $this->assertEquals('Аноним', $post->getAuthor());
        $this->assertEquals('20/01/14 17:23:22', $post->getDate()->format('d/m/y H:i:s'));
        $this->assertEquals('319724', $post->getId());
        $this->assertContains('делать, если расширение', $post->getText());
        $this->assertEquals('', $post->getTitle());
        $this->assertCount(0, $post->getFiles());

        $thread = $this->threadParser->extractThread(file_get_contents(__DIR__ . '/dvach_fixtures/posts/post-thread-71.html'));
        $posts = $thread->getPosts();
        $post = $posts[0];
        $this->assertEquals('пхп', $post->getAuthor());
        $this->assertEquals('24/02/16 17:11:57', $post->getDate()->format('d/m/y H:i:s'));
        $this->assertEquals('665216', $post->getId());
        $this->assertContains('что в пхп ини то же самое</span><br>А ты тот файл который нужно редактируешь? Настройки', $post->getText());
        $this->assertEquals('', $post->getTitle());
        $this->assertCount(0, $post->getFiles());
    }

    /**
     * @dataProvider provideThreadsHtml
     */
    public function testExtractThread(string $pathToThreadHtml)
    {
        $thread = $this->threadParser->extractThread(file_get_contents($pathToThreadHtml));
        $posts = $thread->getPosts();
        // It is enough to check only posts count, because $threadParser throws an exception when parsing fails
        $this->assertGreaterThan(500, $posts->count());
        $this->assertNotEmpty($posts->first()->getAuthor());
        $this->assertNotEmpty($posts->first()->getId());
        $this->assertNotEmpty($posts->first()->getText());
    }

    public function provideThreadsHtml()
    {
        return [
            [__DIR__ . '/dvach_fixtures/1.html'],
            [__DIR__ . '/dvach_fixtures/2.html'],
            [__DIR__ . '/dvach_fixtures/3.html'],
            [__DIR__ . '/dvach_fixtures/6.html'],
            [__DIR__ . '/dvach_fixtures/10.html'],
            [__DIR__ . '/dvach_fixtures/15.html'],
            [__DIR__ . '/dvach_fixtures/17.html'],
            [__DIR__ . '/dvach_fixtures/18.html'],
            [__DIR__ . '/dvach_fixtures/19.html'],
            [__DIR__ . '/dvach_fixtures/20.html'],
            [__DIR__ . '/dvach_fixtures/21.html'],
            [__DIR__ . '/dvach_fixtures/22.html'],
            [__DIR__ . '/dvach_fixtures/23.html'],
            [__DIR__ . '/dvach_fixtures/24.html'],
            [__DIR__ . '/dvach_fixtures/26.html'],
            [__DIR__ . '/dvach_fixtures/27.html'],
            [__DIR__ . '/dvach_fixtures/28.html'],
            [__DIR__ . '/dvach_fixtures/29.html'],
            [__DIR__ . '/dvach_fixtures/30.html'],
            [__DIR__ . '/dvach_fixtures/31.html'],
            [__DIR__ . '/dvach_fixtures/40.html'],
            [__DIR__ . '/dvach_fixtures/32.html'],
            [__DIR__ . '/dvach_fixtures/50.html'],
            [__DIR__ . '/dvach_fixtures/60.html'],
            [__DIR__ . '/dvach_fixtures/77.html'],
            [__DIR__ . '/dvach_fixtures/80.html'],
        ];
    }

    /**
     * @dataProvider providePostsWithOpPostTitles
     */
    public function testOpPostTitleIsCorrect(string $pathToThreadHtml, string $opPostTitle)
    {
        $thread = $this->threadParser->extractThread(file_get_contents($pathToThreadHtml));
        $this->assertEquals($opPostTitle, $thread->getPosts()->first()->getTitle());
    }

    public function providePostsWithOpPostTitles()
    {
        return [
            [
                __DIR__ . '/dvach_fixtures/80.html',
                'Клуб изучающих PHP 80: Последний летний.'
            ],
            [
                __DIR__ . '/dvach_fixtures/6.html',
                'Клуб PHP для начинающих (6)'
            ],
            [
                __DIR__ . '/dvach_fixtures/2.html',
                ''
            ],
            [
                __DIR__ . '/dvach_fixtures/31.html',
                'Клуб изучения PHP 31',
            ]
        ];
    }

    public function testFilesThread80()
    {
        $pathToHtml  = __DIR__ . '/dvach_fixtures/80.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        /** @var File[] $files */
        $files = $posts->first()->getFiles();

        $this->assertCount(4, $files);

        // Image 1
        $this->assertEquals('14719368905530.png', $files[0]->getRelativePath());
        $this->assertEquals(500, $files[0]->getHeight());
        $this->assertEquals(500, $files[0]->getWidth());
        $this->assertEquals('14719368905530s.jpg', $files[0]->getThumbnailRelativePath());

        // Image 2
        $this->assertEquals('14719368905541.jpg', $files[1]->getRelativePath());
        $this->assertEquals(166, $files[1]->getHeight());
        $this->assertEquals(250, $files[1]->getWidth());
        $this->assertEquals('14719368905541s.jpg', $files[1]->getThumbnailRelativePath());

        // Image 3
        $this->assertEquals('14719368905542.jpg', $files[2]->getRelativePath());
        $this->assertEquals(250, $files[2]->getHeight());
        $this->assertEquals(175, $files[2]->getWidth());
        $this->assertEquals('14719368905542s.jpg', $files[2]->getThumbnailRelativePath());

        $this->assertCount(2, $posts[1]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());
    }

    public function testThreadFromGoogleCache()
    {
        $pathToHtml = __DIR__ . '/dvach_fixtures/15.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertGreaterThan(600, $posts->count());

        $this->assertEquals('!xnn2uE3AU.', $posts[0]->getAuthor());

        $this->assertContains('пробелы между строчками и всё заработало', $posts[7]->getText());
        $this->assertCount(1, $posts[7]->getFiles());

        $this->assertContains('будет идти потоковое видео?', $posts->last()->getText());
    }

    public function testWebmParsing()
    {
        $pathToHtml = __DIR__ . '/dvach_fixtures/66.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();

        $postWithWebm = $posts
            ->filter(function (Post $post) { return $post->getId() === 610463; })
            ->first();

        $this->assertContains('.webm', $postWithWebm->getFiles()->first()->getRelativePath());
        $this->assertNotEmpty($postWithWebm->getFiles()->first()->getThumbnailRelativePath());
    }

    public function testFilesCount()
    {
        // All this checks are required!
        $pathToHtml  = __DIR__ . '/dvach_fixtures/1.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(1, $posts[0]->getFiles());
        $this->assertCount(0, $posts[1]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());
        $this->assertCount(1, $posts[3]->getFiles());

        $pathToHtml  = __DIR__ . '/dvach_fixtures/3.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(1, $posts[0]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());

        $pathToHtml  = __DIR__ . '/dvach_fixtures/6.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(1, $posts[0]->getFiles());
        $this->assertCount(1, $posts[1]->getFiles());
        $this->assertCount(0, $posts->last()->getFiles());

        $pathToHtml  = __DIR__ . '/dvach_fixtures/10.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(1, $posts[0]->getFiles());
        $this->assertCount(1, $posts[1]->getFiles());
        $this->assertCount(1, $posts[2]->getFiles());
        $this->assertCount(0, $posts[3]->getFiles());

        $pathToHtml  = __DIR__ . '/dvach_fixtures/15.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(1, $posts[0]->getFiles());
        $this->assertCount(1, $posts[1]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());

        $pathToHtml  = __DIR__ . '/dvach_fixtures/77.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(4, $posts[0]->getFiles());

        $pathToHtml  = __DIR__ . '/dvach_fixtures/60.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(4, $posts[0]->getFiles());

        $pathToHtml  = __DIR__ . '/dvach_fixtures/50.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(4, $posts[0]->getFiles());
        $this->assertCount(3, $posts[1]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());

        $pathToHtml  = __DIR__ . '/dvach_fixtures/40.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(4, $posts[0]->getFiles());
        $this->assertCount(2, $posts[1]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());

        $pathToHtml  = __DIR__ . '/dvach_fixtures/32.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(3, $posts[0]->getFiles());
        $this->assertCount(3, $posts[1]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());

        $pathToHtml  = __DIR__ . '/dvach_fixtures/29.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(1, $posts[0]->getFiles());
        $this->assertCount(1, $posts[1]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());

        $pathToHtml  = __DIR__ . '/dvach_fixtures/27.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(1, $posts[0]->getFiles());
        $this->assertCount(1, $posts[1]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());
        $this->assertCount(1, $posts[3]->getFiles());

        $pathToHtml  = __DIR__ . '/dvach_fixtures/20.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(1, $posts[0]->getFiles());
        $this->assertCount(1, $posts[1]->getFiles());
        $this->assertCount(1, $posts[2]->getFiles());
        $this->assertCount(0, $posts[3]->getFiles());
    }
}
