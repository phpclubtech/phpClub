<?php

declare(strict_types=1);

namespace Tests\ThreadParser;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use phpClub\Entity\{Post, File, Thread};
use phpClub\ThreadParser\ThreadProvider\DvachApiClient;
use PHPUnit\Framework\TestCase;
use Zend\EventManager\EventManagerInterface;

class DvachApiClientTest extends TestCase
{
    public function testThreadsFound()
    {
        $dvachApiClient = $this->createDvachApiClient(
            new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__ . '/dvach_api_fixtures/catalog.json')),
            new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__ . '/dvach_api_fixtures/93a.json')),
            new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__ . '/dvach_api_fixtures/93b.json')),
            new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__ . '/dvach_api_fixtures/92.json'))
        );
        
        $phpThreads = $dvachApiClient->getAlivePhpThreads();
        
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
        $this->assertContains('Первый в этом ИТТ треде.', $thirdPost->getText());
        $this->assertCount(1, $thirdPost->getFiles());
        $this->assertEquals('https://2ch.hk/pr/src/1049651/15035109769900.jpg', $thirdPost->getFiles()->first()->getRemoteUrl());
        
        // TODO: add more tests
    }

    public function testThreadsNotFound()
    {
        $dvachApiClient = $this->createDvachApiClient(
            new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__ . '/dvach_api_fixtures/catalog_without_php_threads.json'))
        );

        $this->assertEmpty($dvachApiClient->getAlivePhpThreads());
    }

    private function createDvachApiClient(Response ...$responses): DvachApiClient
    {
        $mockHandler = new MockHandler($responses);
        $stack = HandlerStack::create($mockHandler);
        $client = new Client(['handler' => $stack]);
        $eventManager = $this->createMock(EventManagerInterface::class);

        return new DvachApiClient($client, $eventManager);
    }
}
