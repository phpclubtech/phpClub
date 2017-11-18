<?php

declare(strict_types=1);

namespace phpClub\Command;

use phpClub\Entity\Thread;
use phpClub\Service\DateConverter;
use phpClub\Service\ThreadImporter;
use phpClub\ThreadParser\{ArhivachThreadParser, DvachApiClient, DvachThreadParser};
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
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
            ->addArgument('source', InputArgument::REQUIRED, 'The source of threads: 2ch-api or path to folder with threads')
            // TODO: option?
            ->addArgument('board', InputArgument::OPTIONAL, 'Board (2ch or arhivach)')
            ->setHelp('Import threads')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $output->writeln('Parsing threads...');
        
        $threads = $this->getThreads($input);

        $output->writeln('Saving threads...');
        
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
     * @throws \Exception
     */
    private function getThreads(InputInterface $input): array
    {
        $source = $input->getArgument('source');

        if ($source === '2ch-api') {
            return $this->dvachApiClient->getAlivePhpThreads();
        }

        if ($source === 'arhivach') {
            $threadIds = $input->getArgument('thread_ids');
        }
        
        if (!is_dir($source)) {
            throw new \Exception('Source option must be "2ch-api" or absolute path to the folder with threads');
        }

        $board = $input->getArgument('board');

        if ($board === '2ch') {
            $threadHtmlParser = new DvachThreadParser(new DateConverter());
        } else if ($board === 'arhivach') {
            $threadHtmlParser = new ArhivachThreadParser(new DateConverter());
        } else {
            throw new \Exception('Board option must be "2ch" or "arhivach"');
        }

        return $threadHtmlParser->parseAllThreads($source);
    }
}