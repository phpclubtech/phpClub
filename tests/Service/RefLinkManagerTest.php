<?php

declare(strict_types=1);

namespace Service;

use phpClub\Entity\Post;
use phpClub\Entity\Thread;
use phpClub\Repository\ThreadRepository;
use Doctrine\ORM\EntityManager;
use phpClub\Service\RefLinkManager;
use Tests\AbstractTestCase;

class RefLinkManagerTest extends AbstractTestCase
{
    /**
     * @var RefLinkManager
     */
    private $refLinkManager;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ThreadRepository
     */
    private $threadRepository;

    public function setUp()
    {
        $this->entityManager = $this->getContainer()->get(EntityManager::class);
        $this->refLinkManager = $this->getContainer()->get(RefLinkManager::class);
        $this->threadRepository = $this->getContainer()->get(ThreadRepository::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    /**
     * @dataProvider provideThreadWithChains
     */
    public function testChain(string $threadHtmlPath, $threadId, array $chains)
    {
        $this->importThreadToDb($threadHtmlPath);
        /** @var Thread $thread */
        $thread = $this->threadRepository->find($threadId);

        $this->refLinkManager->insertChain($thread);

        foreach ($chains as $postId => $expectedChain) {
            $givenChain = $this->refLinkManager->getChain($postId)
                ->map(function (Post $post) { return $post->getId(); })
                ->toArray();
            
            $this->assertEquals($expectedChain, $givenChain);
        }
    }

    public function provideThreadWithChains()
    {
        return [
            [
                __DIR__ . '/../Fixtures/dvach/80.html',
                '825576',
                [
                    // Post from start of the chain
                    828777 => [828777, 828793, 828903, 828952],
                    // Post from end of the  chain
                    828578 => [828561, 828578],
                    // Post without references
                    829034 => [829034],
                    // Post from middle of the chain
                    825608 => [825576, 825608, 825667, 825684, 825685, 825687, 825750, 825768, 825779, 825796, 825875, 825969],
                    // Post from middle of the chain
                    825750 => [825576, 825608, 825667, 825684, 825750, 825768, 825875, 825969],
                    // Post is not exists
                    99999999999 => [],
                ]
            ],
        ];
    }
    
    public function tearDown()
    {
        $this->entityManager->getConnection()->rollBack();
    }
}