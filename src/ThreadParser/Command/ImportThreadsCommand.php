<?php

declare(strict_types=1);

namespace phpClub\ThreadParser\Command;

use phpClub\Entity\Thread;
use phpClub\ThreadParser\ThreadImporter;
use phpClub\ThreadParser\ThreadProvider\DvachApiClient;
use phpClub\ThreadParser\ThreadProvider\ThreadHtmlParser;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportThreadsCommand extends Command
{
    /**
     * @var ThreadImporter
     */
    private $threadImporter;

    /**
     * @var DvachApiClient
     */
    private $dvachApiClient;
    
    public function __construct(ThreadImporter $threadImporter, DvachApiClient $dvachApiClient)
    {
        parent::__construct();
        $this->threadImporter = $threadImporter;
        $this->dvachApiClient = $dvachApiClient;
    }

    protected function configure()
    {
        $this
            ->setName('phpClub:import-threads')
            ->setDescription('Imports threads')
            ->setHelp('This command allows you to create a user...')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $threads = $this->getThreads($input);
        
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $output->writeln('Parsing threads...');
        
        $progress = new ProgressBar($output, count($threads));
        $progress->start();

        $this->threadImporter->on(
            ThreadImporter::EVENT_THREAD_PERSISTED,
            function (Thread $thread) use (&$progress) {
                $progress->advance();
            }
        );
        
        $this->threadImporter->import($threads);

        $progress->finish();
        $output->writeln('');
    }

    /**
     * @param InputInterface $input
     * @return Thread[]
     */
    private function getThreads(InputInterface $input): array
    {
        // TODO: add switch statement based on input
        return $this->dvachApiClient->getAlivePhpThreads();
    }
}