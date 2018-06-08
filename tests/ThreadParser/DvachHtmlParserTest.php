<?php

declare(strict_types=1);

namespace Tests\ThreadParser;

use Tests\AbstractTestCase;
use phpClub\Entity\File;
use phpClub\Entity\Post;
use phpClub\ThreadParser\AbstractThreadParser;
use phpClub\ThreadParser\DvachThreadParser;

class DvachHtmlParserTest extends AbstractTestCase
{
    /**
     * @var DvachThreadParser
     */
    private $threadParser;

    public function setUp()
    {
        $this->threadParser = $this->getContainer()->get(DvachThreadParser::class);
    }

    public function testGetPost()
    {
        $thread = $this->threadParser->extractThread(file_get_contents(__DIR__ . '/../Fixtures/dvach/posts/post-thread-17.html'));
        $post = $thread->getPosts()[0];
        $this->assertEquals('Аноним', $post->getAuthor());
        $this->assertEquals('20/01/14 17:23:22', $post->getDate()->format('d/m/y H:i:s'));
        $this->assertEquals('319724', $post->getId());
        $this->assertContains('делать, если расширение', $post->getText());
        $this->assertEmpty($post->getTitle());
        $this->assertCount(0, $post->getFiles());

        $thread = $this->threadParser->extractThread(file_get_contents(__DIR__ . '/../Fixtures/dvach/posts/post-thread-71.html'));
        $post = $thread->getPosts()[0];
        $this->assertEquals('пхп', $post->getAuthor());
        $this->assertEquals('24/02/16 17:11:57', $post->getDate()->format('d/m/y H:i:s'));
        $this->assertEquals('665216', $post->getId());
        $this->assertContains('что в пхп ини то же самое</span><br>А ты тот файл который нужно редактируешь? Настройки', $post->getText());
        $this->assertEmpty($post->getTitle());
        $this->assertCount(0, $post->getFiles());
    }

    /**
     * @dataProvider provideThreadsHtml
     */
    public function testExtractThread(string $pathToThreadHtml)
    {
        $thread = $this->threadParser->extractThread(file_get_contents($pathToThreadHtml));
        $posts = $thread->getPosts();

        if (!preg_match("~15-fixed\.html$~", $pathToThreadHtml)) {
            // It is enough to check only posts count, because $threadParser throws an exception when parsing fails
            $this->assertGreaterThan(490, $posts->count());
        } else {
            $this->assertGreaterThan(300, $posts->count());
        }
        $this->assertNotEmpty($posts->first()->getAuthor());
        $this->assertNotEmpty($posts->first()->getId());
        $this->assertNotEmpty($posts->first()->getText());
    }

    public function provideThreadsHtml()
    {
        return [
            [__DIR__ . '/../Fixtures/dvach/1-fixed.html'],
            [__DIR__ . '/../Fixtures/dvach/2.html'],
            [__DIR__ . '/../Fixtures/dvach/3.html'],
            [__DIR__ . '/../Fixtures/dvach/4b-fixed.html'],
            [__DIR__ . '/../Fixtures/dvach/6.html'],
            [__DIR__ . '/../Fixtures/dvach/10.html'],
            [__DIR__ . '/../Fixtures/dvach/15-fixed.html'],
            [__DIR__ . '/../Fixtures/dvach/17.html'],
            [__DIR__ . '/../Fixtures/dvach/18.html'],
            [__DIR__ . '/../Fixtures/dvach/19.html'],
            [__DIR__ . '/../Fixtures/dvach/20.html'],
            [__DIR__ . '/../Fixtures/dvach/21.html'],
            [__DIR__ . '/../Fixtures/dvach/22.html'],
            [__DIR__ . '/../Fixtures/dvach/23.html'],
            [__DIR__ . '/../Fixtures/dvach/24.html'],
            [__DIR__ . '/../Fixtures/dvach/26.html'],
            [__DIR__ . '/../Fixtures/dvach/27.html'],
            [__DIR__ . '/../Fixtures/dvach/28.html'],
            [__DIR__ . '/../Fixtures/dvach/29.html'],
            [__DIR__ . '/../Fixtures/dvach/30.html'],
            [__DIR__ . '/../Fixtures/dvach/31.html'],
            [__DIR__ . '/../Fixtures/dvach/40.html'],
            [__DIR__ . '/../Fixtures/dvach/32.html'],
            [__DIR__ . '/../Fixtures/dvach/50.html'],
            [__DIR__ . '/../Fixtures/dvach/60.html'],
            [__DIR__ . '/../Fixtures/dvach/77.html'],
            [__DIR__ . '/../Fixtures/dvach/80.html'],
        ];
    }

    public function testWithThreadsDir()
    {
        $threadDir = __DIR__ . '/../Fixtures/dvach';

        $thread = $this->threadParser->extractThread(file_get_contents($threadDir . '/80.html'), $threadDir);

        $file = $thread->getPosts()[0]->getFiles()[0];

        $this->assertContains($threadDir, $file->getPath());
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
                __DIR__ . '/../Fixtures/dvach/80.html',
                'Клуб изучающих PHP 80: Последний летний.',
            ],
            [
                __DIR__ . '/../Fixtures/dvach/6.html',
                'Клуб PHP для начинающих (6)',
            ],
            [
                __DIR__ . '/../Fixtures/dvach/2.html',
                '',
            ],
            [
                __DIR__ . '/../Fixtures/dvach/31.html',
                'Клуб изучения PHP 31',
            ],
        ];
    }

    public function testFilesThread80()
    {
        $pathToHtml = __DIR__ . '/../Fixtures/dvach/80.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();

        /** @var File[] $files */
        $files = $posts->first()->getFiles();
        $this->assertCount(4, $files);

        // Image 1
        $this->assertEquals('14719368905530.png', $files[0]->getPath());
        $this->assertEquals(500, $files[0]->getHeight());
        $this->assertEquals(500, $files[0]->getWidth());
        $this->assertEquals('14719368905530s.jpg', $files[0]->getThumbPath());

        // Image 2
        $this->assertEquals('14719368905541.jpg', $files[1]->getPath());
        $this->assertEquals(166, $files[1]->getHeight());
        $this->assertEquals(250, $files[1]->getWidth());
        $this->assertEquals('14719368905541s.jpg', $files[1]->getThumbPath());

        // Image 3
        $this->assertEquals('14719368905542.jpg', $files[2]->getPath());
        $this->assertEquals(250, $files[2]->getHeight());
        $this->assertEquals(175, $files[2]->getWidth());
        $this->assertEquals('14719368905542s.jpg', $files[2]->getThumbPath());
        $this->assertCount(2, $posts[1]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());
    }

    public function testThreadFromGoogleCache()
    {
        $pathToHtml = __DIR__ . '/../Fixtures/dvach/15-fixed.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertGreaterThan(300, $posts->count());
        $this->assertEquals('!xnn2uE3AU.', $posts[0]->getAuthor());
        $this->assertContains('пробелы между строчками и всё заработало', $posts[7]->getText());
        $this->assertContains('будет идти потоковое видео?', $posts->last()->getText());
    }

    public function testWebmParsing()
    {
        $pathToHtml = __DIR__ . '/../Fixtures/dvach/66.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));

        $postWithWebm = $thread->getPosts()
            ->filter(function (Post $post) {
                return $post->getId() === 610463;
            })
            ->first();

        $this->assertContains('.webm', $postWithWebm->getFiles()->first()->getPath());
        $this->assertNotEmpty($postWithWebm->getFiles()->first()->getThumbPath());
    }

    public function testFilesCount()
    {
        // All this checks are required!
        $pathToHtml = __DIR__ . '/../Fixtures/dvach/1-fixed.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(1, $posts[0]->getFiles());
        $this->assertCount(0, $posts[1]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());
        $this->assertCount(1, $posts[3]->getFiles());

        $pathToHtml = __DIR__ . '/../Fixtures/dvach/3.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(1, $posts[0]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());

        $pathToHtml = __DIR__ . '/../Fixtures/dvach/6.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(1, $posts[0]->getFiles());
        $this->assertCount(1, $posts[1]->getFiles());
        $this->assertCount(0, $posts->last()->getFiles());

        $pathToHtml = __DIR__ . '/../Fixtures/dvach/10.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(1, $posts[0]->getFiles());
        $this->assertCount(1, $posts[1]->getFiles());
        $this->assertCount(1, $posts[2]->getFiles());
        $this->assertCount(0, $posts[3]->getFiles());

        $pathToHtml = __DIR__ . '/../Fixtures/dvach/77.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(4, $posts[0]->getFiles());

        $pathToHtml = __DIR__ . '/../Fixtures/dvach/60.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(4, $posts[0]->getFiles());

        $pathToHtml = __DIR__ . '/../Fixtures/dvach/50.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(4, $posts[0]->getFiles());
        $this->assertCount(3, $posts[1]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());

        $pathToHtml = __DIR__ . '/../Fixtures/dvach/40.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(4, $posts[0]->getFiles());
        $this->assertCount(2, $posts[1]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());

        $pathToHtml = __DIR__ . '/../Fixtures/dvach/32.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(3, $posts[0]->getFiles());
        $this->assertCount(3, $posts[1]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());

        $pathToHtml = __DIR__ . '/../Fixtures/dvach/29.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(1, $posts[0]->getFiles());
        $this->assertCount(1, $posts[1]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());

        $pathToHtml = __DIR__ . '/../Fixtures/dvach/27.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(1, $posts[0]->getFiles());
        $this->assertCount(1, $posts[1]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());
        $this->assertCount(1, $posts[3]->getFiles());

        $pathToHtml = __DIR__ . '/../Fixtures/dvach/20.html';
        $thread = $this->threadParser->extractThread(file_get_contents($pathToHtml));
        $posts = $thread->getPosts();
        $this->assertCount(1, $posts[0]->getFiles());
        $this->assertCount(1, $posts[1]->getFiles());
        $this->assertCount(1, $posts[2]->getFiles());
        $this->assertCount(0, $posts[3]->getFiles());
    }

    public function testCloudflareEmailDecoder()
    {
        $email = AbstractThreadParser::decodeCfEmail('50243835373c253510243f223d31393c7e3f2237');
        $this->assertEquals('theglue@tormail.org', $email);
    }
}
