<?php

declare(strict_types=1);

namespace phpClub\Command;

use phpClub\Entity\Thread;
use phpClub\Service\ThreadImporter;
use phpClub\ThreadParser\{ArhivachClient, ArhivachThreadParser, DvachApiClient, DvachThreadParser};
use Psr\Container\ContainerInterface;
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

    /**
     * @var ArhivachClient
     */
    private $arhivachClient;

    /**
     * @var DvachThreadParser
     */
    private $dvachThreadParser;

    /**
     * @var ArhivachThreadParser
     */
    private $arhivachThreadParser;

    public function __construct(ContainerInterface $di) 
    {
        parent::__construct();
        // TODO: get rid of this after migrate to PHP-DI
        $this->threadImporter = $di->get(ThreadImporter::class);
        $this->dvachApiClient = $di->get(DvachApiClient::class);
        $this->arhivachClient = $di->get(ArhivachClient::class);
        $this->dvachThreadParser = $di->get(DvachThreadParser::class);
        $this->arhivachThreadParser = $di->get(ArhivachThreadParser::class);
    }

    protected function configure()
    {
        $this
            ->setName('phpClub:import-threads')
            ->setDescription('Imports threads')
            ->addOption(
                'source',
                's',
                InputArgument::OPTIONAL,
                'The source of threads: "2ch-api" or "arhivach" site'
            )
            ->addOption(
                'dir',
                'd',
                InputArgument::OPTIONAL,
                'Absolute path to the local 2ch threads'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $output->writeln('Parsing threads...');
        
        $threads = $this->getThreads($input);

        $output->writeln('Saving threads...');
        
        $progress = new ProgressBar($output, count($threads));
        $progress->setMessage('Thread saving progress');
        $progress->start();

        $this->threadImporter->on(
            ThreadImporter::EVENT_THREAD_SAVED,
            function () use (&$progress) {
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
        if ($source = $input->getOption('source')) {
            if ($source === '2ch-api') {
                return $this->dvachApiClient->getAlivePhpThreads();
            }

            if ($source === 'arhivach') {
                return $this->arhivachClient->getPhpThreads($this->getDefaultArhivachThreads());
            }

            throw new \Exception('Source option must be "2ch-api" or "arhivach"');
        }

        if (!$threadsDir = $input->getOption('dir')) {
            throw new \Exception('You need to specify --dir or --source');
        }

        $threadHtmlPaths = glob($threadsDir . '/*/*.htm*');

        if (!$threadHtmlPaths) {
            throw new \Exception('No threads found in ' . $threadsDir);
        }

        return array_map(function ($threadHtmlPath) {
            return $this->dvachThreadParser->extractThread(file_get_contents($threadHtmlPath), dirname($threadHtmlPath));
        }, $threadHtmlPaths);
    }

    private function getDefaultArhivachThreads(): array
    {
        return [
            25 => 'http://arhivach.org/thread/25318/',
            79 => 'http://arhivach.org/thread/191923/',
            '79b' => 'http://arhivach.org/thread/193343/', // Нелегетимный 79-й тред
            80 => 'http://arhivach.org/thread/197740/',
            81 => 'http://arhivach.org/thread/204328/',
            82 => 'http://arhivach.org/thread/213097/',
            83 => 'http://arhivach.org/thread/216627/',
            84 => 'http://arhivach.org/thread/224683/',
            85 => 'http://arhivach.org/thread/233392/',
            86 => 'http://arhivach.org/thread/245785/',
            87 => 'http://arhivach.org/thread/249265/',
            88 => 'http://arhivach.org/thread/254710/',
            89 => 'http://arhivach.org/thread/261841/',
            90 => 'http://arhivach.org/thread/266631/',
            91 => 'http://arhivach.org/thread/282397/',
            92 => 'http://arhivach.org/thread/282400/',
            93 => 'http://arhivach.org/thread/302513/',
            94 => 'http://arhivach.org/thread/302511/',
            95 => 'http://arhivach.org/thread/312253/',
        ];
    }
}