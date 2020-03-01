<?php

declare(strict_types=1);

namespace Tests\BoardClient;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use phpClub\BoardClient\DvachClient;
use phpClub\Entity\Post;
use phpClub\Entity\Thread;
use phpClub\Util\FsUtil;
use PHPUnit\Framework\TestCase;

class DvachClientTest extends TestCase
{
    public function testThreads92and93(): void
    {
        $dvachClient = $this->createDvachApiClient(
            new Response(200, ['Content-Type' => 'application/json'], FsUtil::getContents(__DIR__ . '/../Fixtures/dvach_api/catalog.json')),
            new Response(200, ['Content-Type' => 'application/json'], FsUtil::getContents(__DIR__ . '/../Fixtures/dvach_api/93a.json')),
            new Response(200, ['Content-Type' => 'application/json'], FsUtil::getContents(__DIR__ . '/../Fixtures/dvach_api/93b.json')),
            new Response(200, ['Content-Type' => 'application/json'], FsUtil::getContents(__DIR__ . '/../Fixtures/dvach_api/92.json'))
        );

        $phpThreads = $dvachClient->getAlivePhpThreads(DvachClient::RETURN_THREADS);

        $this->assertCount(3, $phpThreads);

        /** @var Thread $phpThread93a */
        $phpThread93a = current($phpThreads);
        $this->assertCount(9, $phpThread93a->getPosts());

        /** @var Post $opPost */
        $opPost = $phpThread93a->getPosts()->first();
        $this->assertCount(4, $opPost->getFiles());
        $this->assertEquals('Клуб изучающих PHP и webdev #93', $opPost->getTitle());
        $this->assertEquals('1049651', $opPost->getId());
        $this->assertEquals(500, $opPost->getFiles()->first()->getHeight());
        $this->assertEquals(683, $opPost->getFiles()[1]->getHeight());
        $this->assertEquals('Аноним', $opPost->getAuthor());

        $thirdPost = $phpThread93a->getPosts()[2];
        $this->assertStringContainsString('Первый в этом ИТТ треде.', $thirdPost->getText());
        $this->assertCount(1, $thirdPost->getFiles());
        $this->assertEquals('https://2ch.hk/pr/src/1049651/15035109769900.jpg', $thirdPost->getFiles()->first()->getPath());
        $this->assertEquals('https://2ch.hk/pr/thumb/1049651/15035109769900s.jpg', $thirdPost->getFiles()->first()->getThumbPath());
    }

    public function testThreadsNotFound(): void
    {
        $dvachApiClient = $this->createDvachApiClient(
            new Response(200, ['Content-Type' => 'application/json'], FsUtil::getContents(__DIR__ . '/../Fixtures/dvach_api/catalog_without_php_threads.json'))
        );
        $this->assertEmpty($dvachApiClient->getAlivePhpThreads(DvachClient::RETURN_THREADS));
    }

    private function createDvachApiClient(Response ...$responses): DvachClient
    {
        $mockHandler = new MockHandler($responses);
        $stack = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $stack]);

        return new DvachClient($client);
    }
}
