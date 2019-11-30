<?php

declare(strict_types=1);

namespace Tests\ThreadParser;

use phpClub\Entity\File;
use phpClub\ThreadParser\ArhivachThreadParser;
use phpClub\Util\FsUtil;
use Tests\AbstractTestCase;

class ArhivachThreadParserTest extends AbstractTestCase
{
    private ArhivachThreadParser $threadParser;

    public function setUp(): void
    {
        $this->threadParser = $this->getContainer()->get(ArhivachThreadParser::class);
    }

    public function testThread83()
    {
        $thread = $this->threadParser->extractThread(FsUtil::getContents(__DIR__ . '/../Fixtures/arhivach/83.html'));
        $posts = $thread->getPosts();
        $post = $posts[16];
        $this->assertEquals('Аноним', $post->getAuthor());
        $this->assertEquals('24/11/16 23:34:24', $post->getDate()->format('d/m/y H:i:s'));
        $this->assertEquals('881710', $post->getId());
        $this->assertStringContainsString('Помогите с циклами, ребята.', $post->getText());
        $this->assertEmpty($post->getTitle());
        $this->assertTrue($post->getFiles()->isEmpty());

        $post = $posts[452];
        $this->assertStringContainsString('Лучше всего почитать у Фаулера', $post->getText());
        $this->assertEquals('Аноним', $post->getAuthor());
        $this->assertEmpty($post->getTitle());
        $this->assertTrue($post->getFiles()->isEmpty());

        $post = $posts->last();
        $this->assertEquals('Аноним', $post->getAuthor());
        $this->assertStringContainsString('Проверил файлообменник и написал в новом треде', $post->getText());
        $this->assertEquals('github.com/fidnex/filehost', $post->getTitle());
        $this->assertTrue($post->getFiles()->isEmpty());

        $post = $posts[92];
        $this->assertCount(3, $post->getFiles());
    }

    public function testThread90()
    {
        $thread = $this->threadParser->extractThread(FsUtil::getContents(__DIR__ . '/../Fixtures/arhivach/90.html'));
        $posts = $thread->getPosts();
        $post = $posts[27];
        $this->assertEquals('Аноним', $post->getAuthor());
        $this->assertEquals('04/06/17 04:56:50', $post->getDate()->format('d/m/y H:i:s'));
        $this->assertEquals('1000752', $post->getId());
        $this->assertStringContainsString('rand() использует линейный ГПСЧ, который абсолютно предсказуем и имеет относительно небольшой период', $post->getText());
        $this->assertEmpty($post->getTitle());
        $this->assertCount(0, $post->getFiles());

        $post = $posts[15];
        $this->assertEquals('someApprentice', $post->getAuthor());
        $this->assertEquals('03/06/17 20:05:31', $post->getDate()->format('d/m/y H:i:s'));
        $this->assertEquals('1000566', $post->getId());
        $this->assertStringContainsString('sudo searchd --config sphinx.conf', $post->getText());
        $this->assertEmpty($post->getTitle());
        $this->assertCount(0, $post->getFiles());

        $post = $posts[303];
        $this->assertEquals('14/06/17 09:46:46', $post->getDate()->format('d/m/y H:i:s'));
        $this->assertEquals('Аноним', $post->getAuthor());
        $this->assertEquals('1005616', $post->getId());
        $this->assertStringContainsString('Для передачи параметров есть следующие способы:', $post->getText());
        $this->assertEmpty($post->getTitle());
        $this->assertCount(4, $post->getFiles());

        $post = $posts[856];
        $webm = $post->getFiles()->first();
        $this->assertStringContainsString('.webm', $webm->getPath());
        $this->assertNotEmpty($webm->getThumbPath());

        $givenFileNames = $posts[0]->getFiles()->map(function (File $file) {
            return $file->getName();
        })->toArray();

        $expectedFileNames = ['php-noob-1.png', 'cat-cafe-osaka.jpg', 'l0-sensei.jpg', 'just-google-it.jpg'];

        $this->assertEquals($expectedFileNames, $givenFileNames);
    }

    public function testThread25()
    {
        $thread = $this->threadParser->extractThread(FsUtil::getContents(__DIR__ . '/../Fixtures/arhivach/25.html'));
        $posts = $thread->getPosts();

        $post = $posts[8];
        $this->assertEquals('!xnn2uE3AU.', $post->getAuthor());
        $this->assertEquals('09/06/14 14:49:35', $post->getDate()->format('d/m/y H:i:s'));
        $this->assertEquals('360403', $post->getId());
        $this->assertStringContainsString('http://brainstorage.me/jobs?q=haskell', $post->getText());
        $this->assertEmpty($post->getTitle());
        $this->assertCount(1, $post->getFiles());
        $this->assertNotEmpty($post->getFiles()->first()->getPath());
        $this->assertNotEmpty($post->getFiles()->first()->getThumbPath());

        $post = $posts[53];
        $this->assertEquals('Аноним', $post->getAuthor());
        $this->assertEquals('10/06/14 16:44:24', $post->getDate()->format('d/m/y H:i:s'));
        $this->assertEquals('360614', $post->getId());
        $this->assertStringContainsString('Аноны, помогите!', $post->getText());
        $this->assertEmpty($post->getTitle());
        $this->assertCount(1, $post->getFiles());
        $this->assertNotEmpty($post->getFiles()->first()->getPath());
        $this->assertNotEmpty($post->getFiles()->first()->getThumbPath());
    }

    /**
     * @dataProvider provideThreadsHtml
     */
    public function testExtractThread($pathToThreadHtml)
    {
        $thread = $this->threadParser->extractThread(FsUtil::getContents($pathToThreadHtml));
        $this->assertGreaterThan(100, $thread->getPosts()->count());
        $opPost = $thread->getPosts()->first();
        $this->assertNotEmpty($opPost->getAuthor());
        $this->assertNotEmpty($opPost->getId());
        $this->assertNotEmpty($opPost->getText());
        $this->assertGreaterThanOrEqual(1, $opPost->getFiles()->count());
    }

    public function provideThreadsHtml()
    {
        return [
            [__DIR__ . '/../Fixtures/arhivach/25.html'],
            [__DIR__ . '/../Fixtures/arhivach/83.html'],
            [__DIR__ . '/../Fixtures/arhivach/90.html'],
        ];
    }

    public function testFiles()
    {
        $pathToHtml = __DIR__ . '/../Fixtures/arhivach/83.html';
        $thread = $this->threadParser->extractThread(FsUtil::getContents($pathToHtml));
        $posts = $thread->getPosts();
        /** @var File[] $files */
        $files = $posts->first()->getFiles();

        $this->assertCount(4, $files);

        // Image 1
        $this->assertEquals('https://arhivach.org/storage2/7/11/71139561f22f2ea253a15d6f442457f6.png', $files[0]->getPath());
        $this->assertEquals(500, $files[0]->getWidth());
        $this->assertEquals(500, $files[0]->getHeight());
        $this->assertEquals('https://arhivach.org/storage2/t/71139561f22f2ea253a15d6f442457f6.png', $files[0]->getThumbPath());

        // Image 2
        $this->assertEquals('https://arhivach.org/storage2/8/63/8631bf83d67be92f483f5cff54c2ed5b.jpg', $files[1]->getPath());
        $this->assertEquals(1024, $files[1]->getWidth());
        $this->assertEquals(683, $files[1]->getHeight());
        $this->assertEquals('https://arhivach.org/storage2/t/8631bf83d67be92f483f5cff54c2ed5b.jpg', $files[1]->getThumbPath());

        // Image 3
        $this->assertEquals('https://arhivach.org/storage2/1/b5/1b529b966894cfc14379bc5a0fea5455.png', $files[2]->getPath());
        $this->assertEquals(853, $files[2]->getWidth());
        $this->assertEquals(480, $files[2]->getHeight());
        $this->assertEquals('https://arhivach.org/storage2/t/1b529b966894cfc14379bc5a0fea5455.png', $files[2]->getThumbPath());

        $this->assertCount(1, $posts[1]->getFiles());
        $this->assertCount(0, $posts[2]->getFiles());
    }
}
