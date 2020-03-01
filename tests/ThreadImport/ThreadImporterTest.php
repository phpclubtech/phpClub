<?php

declare(strict_types=1);

namespace Tests\ThreadImport;

use Doctrine\ORM\EntityManager;
use Tests\AbstractTestCase;

class ThreadImporterTest extends AbstractTestCase
{
    private EntityManager $entityManager;

    public function setUp(): void
    {
        $this->entityManager = $this->getContainer()->get(EntityManager::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    public function tearDown(): void
    {
        $this->entityManager->getConnection()->rollBack();
    }

    public function testCanImportTwice(): void 
    {
    	$threadHtml = __DIR__ . '/../Fixtures/dvach/80.html';
    	$this->importThreadToDb($threadHtml);
    	$this->importThreadToDb($threadHtml);
    }
}
