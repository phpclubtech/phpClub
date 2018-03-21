<?php

declare(strict_types=1);

namespace phpClub\Command;

use Doctrine\ORM\EntityManagerInterface;
use phpClub\Repository\ThreadRepository;
use phpClub\ThreadImport\ChainManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RebuildChainsCommand extends Command
{
    /**
     * @var ChainManager
     */
    private $chainManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ThreadRepository
     */
    private $threadRepository;

    public function __construct(
        ChainManager $chainManager,
        EntityManagerInterface $entityManager,
        ThreadRepository $threadRepository
    ) {
        parent::__construct();
        $this->chainManager = $chainManager;
        $this->entityManager = $entityManager;
        $this->threadRepository = $threadRepository;
    }

    protected function configure()
    {
        $this
            ->setName('rebuild-chains')
            ->setDescription('Rebuilds chains without re-importing threads');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Removing chains...');

        $this->chainManager->removeAllChains();

        $output->writeln('Building chains...');

        foreach ($this->threadRepository->findAll() as $thread) {
            $this->chainManager->insertChain($thread);
        }

        $output->writeln('Done');
    }
}
