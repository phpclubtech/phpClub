<?php

declare(strict_types=1);

namespace Tests\ThreadParser;

use PHPUnit\Framework\TestCase;
use phpClub\ThreadParser\DTO\Post;
use phpClub\ThreadParser\Thread\DvachThread;
use phpClub\ThreadParser\{ThreadHtmlParser, DateConverter};

class DvachHtmlParserTest extends TestCase
{
    /**
     * @var ThreadHtmlParser
     */
    private $threadParser;

    public function setUp()
    {
        $this->threadParser = new ThreadHtmlParser(new DateConverter(), new DvachThread());
    }
    
    public function testGetPost()
    {
        $posts = $this->threadParser->getPosts(file_get_contents(__DIR__ . '/dvach_fixtures/posts/post-thread-17.html'));
        $post = $posts[0];
        $this->assertEquals('Аноним', $post->author);
        $this->assertEquals('20/01/14 17:23:22', $post->date->format('d/m/y H:i:s'));
        $this->assertEquals('319724', $post->id);
        $this->assertContains('делать, если расширение', $post->text);
        $this->assertEquals('', $post->title);
        $this->assertCount(0, $post->files);

        $posts = $this->threadParser->getPosts(file_get_contents(__DIR__ . '/dvach_fixtures/posts/post-thread-71.html'));
        $post = $posts[0];
        $this->assertEquals('пхп', $post->author);
        $this->assertEquals('24/02/16 17:11:57', $post->date->format('d/m/y H:i:s'));
        $this->assertEquals('665216', $post->id);
        $this->assertContains('что в пхп ини то же самое</span><br>А ты тот файл который нужно редактируешь? Настройки', $post->text);
        $this->assertEquals('', $post->title);
        $this->assertCount(0, $post->files);
    }

    /**
     * @dataProvider provideThreadsHtml
     */
    public function testGetPosts($pathToThreadHtml)
    {
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToThreadHtml));
        $this->assertGreaterThan(500, count($threadArray));
        $this->assertNotEmpty($threadArray[0]->author);
        $this->assertNotEmpty($threadArray[0]->id);
        $this->assertNotEmpty($threadArray[0]->text);
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
    public function testOpPostTitleIsCorrect($pathToThreadHtml, $opPostTitle)
    {
        $postsArray = $this->threadParser->getPosts(file_get_contents($pathToThreadHtml));
        $this->assertEquals($opPostTitle, $postsArray[0]->title);
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
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToHtml));
        $files       = $threadArray[0]->files;

        $this->assertCount(4, $files);

        // Image 1
        $this->assertEquals('14719368905530.png', $files[0]->fullName);
        $this->assertEquals(500, $files[0]->height);
        $this->assertEquals(500, $files[0]->width);
        $this->assertEquals('14719368905530s.jpg', $files[0]->thumbName);

        // Image 2
        $this->assertEquals('14719368905541.jpg', $files[1]->fullName);
        $this->assertEquals(166, $files[1]->height);
        $this->assertEquals(250, $files[1]->width);
        $this->assertEquals('14719368905541s.jpg', $files[1]->thumbName);

        // Image 3
        $this->assertEquals('14719368905542.jpg', $files[2]->fullName);
        $this->assertEquals(250, $files[2]->height);
        $this->assertEquals(175, $files[2]->width);
        $this->assertEquals('14719368905542s.jpg', $files[2]->thumbName);

        $this->assertCount(2, $threadArray[1]->files);
        $this->assertCount(0, $threadArray[2]->files);
    }

    public function testFilesCount()
    {
        $pathToHtml  = __DIR__ . '/dvach_fixtures/1.html';
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToHtml));
        $this->assertCount(1, $threadArray[0]->files);
        $this->assertCount(0, $threadArray[1]->files);
        $this->assertCount(0, $threadArray[2]->files);
        $this->assertCount(1, $threadArray[3]->files);

        $pathToHtml  = __DIR__ . '/dvach_fixtures/3.html';
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToHtml));
        $this->assertCount(1, $threadArray[0]->files);
        $this->assertCount(0, $threadArray[2]->files);

        $pathToHtml  = __DIR__ . '/dvach_fixtures/6.html';
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToHtml));
        $this->assertCount(1, $threadArray[0]->files);
        $this->assertCount(1, $threadArray[1]->files);
        $this->assertCount(0, end($threadArray)->files);

        $pathToHtml  = __DIR__ . '/dvach_fixtures/10.html';
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToHtml));
        $this->assertCount(1, $threadArray[0]->files);
        $this->assertCount(1, $threadArray[1]->files);
        $this->assertCount(1, $threadArray[2]->files);
        $this->assertCount(0, $threadArray[3]->files);

        $pathToHtml  = __DIR__ . '/dvach_fixtures/15.html';
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToHtml));
        $this->assertCount(1, $threadArray[0]->files);
        $this->assertCount(1, $threadArray[1]->files);
        $this->assertCount(0, $threadArray[2]->files);

        $pathToHtml  = __DIR__ . '/dvach_fixtures/77.html';
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToHtml));
        $this->assertCount(4, $threadArray[0]->files);

        $pathToHtml  = __DIR__ . '/dvach_fixtures/60.html';
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToHtml));
        $this->assertCount(4, $threadArray[0]->files);

        $pathToHtml  = __DIR__ . '/dvach_fixtures/50.html';
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToHtml));
        $this->assertCount(4, $threadArray[0]->files);
        $this->assertCount(3, $threadArray[1]->files);
        $this->assertCount(0, $threadArray[2]->files);

        $pathToHtml  = __DIR__ . '/dvach_fixtures/40.html';
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToHtml));
        $this->assertCount(4, $threadArray[0]->files);
        $this->assertCount(2, $threadArray[1]->files);
        $this->assertCount(0, $threadArray[2]->files);

        $pathToHtml  = __DIR__ . '/dvach_fixtures/32.html';
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToHtml));
        $this->assertCount(3, $threadArray[0]->files);
        $this->assertCount(3, $threadArray[1]->files);
        $this->assertCount(0, $threadArray[2]->files);

        $pathToHtml  = __DIR__ . '/dvach_fixtures/29.html';
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToHtml));
        $this->assertCount(1, $threadArray[0]->files);
        $this->assertCount(1, $threadArray[1]->files);
        $this->assertCount(0, $threadArray[2]->files);

        $pathToHtml  = __DIR__ . '/dvach_fixtures/27.html';
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToHtml));
        $this->assertCount(1, $threadArray[0]->files);
        $this->assertCount(1, $threadArray[1]->files);
        $this->assertCount(0, $threadArray[2]->files);
        $this->assertCount(1, $threadArray[3]->files);

        $pathToHtml  = __DIR__ . '/dvach_fixtures/20.html';
        $threadArray = $this->threadParser->getPosts(file_get_contents($pathToHtml));
        $this->assertCount(1, $threadArray[0]->files);
        $this->assertCount(1, $threadArray[1]->files);
        $this->assertCount(1, $threadArray[2]->files);
        $this->assertCount(0, $threadArray[3]->files);
    }

    public function testThreadFromGoogleCache()
    {
        $pathToHtml  = __DIR__ . '/dvach_fixtures/15.html';
        $posts = $this->threadParser->getPosts(file_get_contents($pathToHtml));
        $this->assertGreaterThan(600, count($posts));

        $this->assertEquals('!xnn2uE3AU.', $posts[0]->author);

        $this->assertContains('пробелы между строчками и всё заработало', $posts[7]->text);
        $this->assertCount(1, $posts[7]->files);

        $this->assertContains('будет идти потоковое видео?', end($posts)->text);
    }

    public function testWebmParsing()
    {
        $pathToHtml = __DIR__ . '/dvach_fixtures/66.html';
        $posts = $this->threadParser->getPosts(file_get_contents($pathToHtml));

        $postWithWebm = current(array_filter($posts, function (Post $post) {
            return $post->id == 610463;
        }));

        $this->assertContains('.webm', $postWithWebm->files[0]->fullName);
        $this->assertNotEmpty($postWithWebm->files[0]->thumbName);
    }
}
