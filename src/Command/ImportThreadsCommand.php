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
                'The source of threads: 2ch-api or arhivach'
            )
            ->addOption(
                'urls',
                'u',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'List of urls'
            )
            ->addOption(
                'dir',
                'd',
                InputArgument::OPTIONAL,
                'Absolute path to the local threads'
            )
            ->addOption(
                'board',
                'b',
                InputArgument::OPTIONAL,
                'Board (2ch or arhivach)'
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
                $threadUrls = $input->getOption('urls') ? explode(',', $input->getOption('urls')) : $this->getAllArchivedPhpThreads(); 
                return $this->arhivachClient->getPhpThreads($threadUrls);
            }

            throw new \Exception('Source option must be "2ch-api" or "arhivach"');
        }

        if (!$threadsDir = $input->getOption('dir')) {
            throw new \Exception('You need to specify --dir or --source');
        }

        if (!$board = $input->getOption('board')) {
            throw new \Exception('You need to specify board ("2ch" or "arhivach")');
        }

        if ($board === '2ch') {
            $threadParser = $this->dvachThreadParser;
        } else if ($board === 'arhivach') {
            $threadParser = $this->arhivachThreadParser;
        } else {
            throw new \Exception('Board option must be "2ch" or "arhivach"');
        }
        
        $threadHtmlPaths = glob($threadsDir . '/*/*.htm*');

        if (!$threadHtmlPaths) {
            throw new \Exception('No threads found in ' . $threadsDir);
        }

        return array_map(function ($threadHtmlPath) use ($threadParser) {
            return $threadParser->extractThread(
                file_get_contents($threadHtmlPath),
                dirname($threadHtmlPath)
            );
        }, $threadHtmlPaths);
    }

    private function getAllArchivedPhpThreads(): array
    {
        return [
/*            43 => 'http://arhivach.org/thread/63085/',
            44 => 'http://arhivach.org/thread/65094/',

            46 => 'http://arhivach.org/thread/73389/',

            54 => 'http://arhivach.org/thread/100197/',
            55 => 'http://arhivach.org/thread/100459/',
            56 => 'http://arhivach.org/thread/99744/',
            57 => 'http://arhivach.org/thread/103663/',
            58 => 'http://arhivach.org/thread/109400/',
            59 => 'http://arhivach.org/thread/108529/',
            61 => 'http://arhivach.org/thread/117571/',
            62 => 'http://arhivach.org/thread/121251/',
            63 => 'http://arhivach.org/thread/127521/',
            64 => 'http://arhivach.org/thread/131154/',

            67 => 'http://arhivach.org/thread/139825/',*/

/*            72 => 'http://arhivach.org/thread/159611/',
            73 => 'http://arhivach.org/thread/159612/',
            74 => 'http://arhivach.org/thread/163817/',
            75 => 'http://arhivach.org/thread/164321/',

            79 => 'http://arhivach.org/thread/191923/',
            '79b' => 'http://arhivach.org/thread/193343/', // Нелегетимный 79-й тред
            80 => 'http://arhivach.org/thread/197740/',
            81 => 'http://arhivach.org/thread/204328/',
            82 => 'http://arhivach.org/thread/213097/',
            83 => 'http://arhivach.org/thread/216627/',
            84 => 'http://arhivach.org/thread/224683/',
            85 => 'http://arhivach.org/thread/233392/',*/

/*            86 => 'http://arhivach.org/thread/245785/',
            87 => 'http://arhivach.org/thread/249265/',
            88 => 'http://arhivach.org/thread/254710/',
            89 => 'http://arhivach.org/thread/261841/',
            90 => 'http://arhivach.org/thread/266631/',
            91 => 'http://arhivach.org/thread/282397/',
            92 => 'http://arhivach.org/thread/282400/',
            93 => 'http://arhivach.org/thread/302513/',
            94 => 'http://arhivach.org/thread/302511/',
            95 => 'http://arhivach.org/thread/312253/',*/
        ];
    }
}