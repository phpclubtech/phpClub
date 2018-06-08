<?php

declare(strict_types=1);

namespace phpClub\Command;

use phpClub\BoardClient\ArhivachClient;
use phpClub\BoardClient\DvachClient;
use phpClub\Entity\Thread;
use phpClub\ThreadImport\ThreadImporter;
use phpClub\ThreadParser\ArhivachThreadParser;
use phpClub\ThreadParser\DvachThreadParser;
use phpClub\ThreadParser\MDvachThreadParser;
use phpClub\ThreadParser\ThreadParseException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportThreadsCommand extends Command
{
    /**
     * @var ThreadImporter
     */
    private $threadImporter;

    /**
     * @var DvachClient
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

    /**
     * @var MDvachThreadParser
     */
    private $mDvachThreadParser;

    public function __construct(
        ThreadImporter $threadImporter,
        DvachClient $dvachApiClient,
        ArhivachClient $arhivachClient,
        DvachThreadParser $dvachThreadParser,
        MDvachThreadParser $mDvachThreadParser,
        ArhivachThreadParser $arhivachThreadParser
    ) {
        $this->threadImporter = $threadImporter;
        $this->dvachApiClient = $dvachApiClient;
        $this->arhivachClient = $arhivachClient;
        $this->dvachThreadParser = $dvachThreadParser;
        $this->mDvachThreadParser = $mDvachThreadParser;
        $this->arhivachThreadParser = $arhivachThreadParser;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('import-threads');
        $this->setDescription('Imports threads from remote server or local HTML files');
        $this->addOption(
            'source',
            's',
            InputOption::VALUE_REQUIRED,
            'Import all threads from remote server, possible values: "2ch-api" or "arhivach"'
        );

        $this->addOption(
            'dir',
            'd',
            InputOption::VALUE_REQUIRED,
            'Load HTML files located 2 levels below this folder. E.g. if you specify /tmp/t, then thread path should be like /tmp/t/thread-1/1234.html'
        );

        $this->addOption(
            'file',
            'f',
            InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
            'Path to HTML files with threads. Can contain glob wildcards, e.g. /tmp/threads/*.html.'
        );

        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Do not save anything to disk or database, just try to parse thread files. Can be useful for testing.'
        );

        $this->addOption(
            'skip-broken',
            null,
            InputOption::VALUE_NONE,
            'Skip threads that cannot be parsed instead of aborting'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isDryRun = (bool) $input->getOption('dry-run');

        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $output->writeln('Parsing threads...');

        $threads = $this->getThreads($input, $output);

        if ($isDryRun) {
            $output->writeln("Dry run, don't save anything");
        } else {
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
    }

    /**
     * @param InputInterface $input
     *
     * @throws \Exception
     *
     * @return Thread[]
     */
    private function getThreads(InputInterface $input, OutputInterface $output): array
    {
        $source = $input->getOption('source');

        if ($source) {
            if ($source === '2ch-api') {
                return $this->dvachApiClient->getAlivePhpThreads();
            }

            if ($source === 'arhivach') {
                return $this->arhivachClient->getPhpThreads($this->getDefaultArhivachThreads());
            }

            throw new \Exception('Source option must be "2ch-api" or "arhivach"');
        }

        // Is an array of glob expressions
        $fileGlobs = $input->getOption('file');
        $threadsDir = $input->getOption('dir');
        $skipBroken = (bool) $input->getOption('skip-broken');

        if (!$threadsDir && !$fileGlobs) {
            throw new \Exception('You need to specify --dir, --source, or --file');
        }

        // Array of resolved paths to HTML files
        $htmlPaths = [];

        if ($fileGlobs) {
            foreach ($fileGlobs as $glob) {
                $paths = glob($glob, GLOB_BRACE | GLOB_ERR);
                $htmlPaths = array_merge($htmlPaths, $paths);
            }
        }

        if ($threadsDir) {
            $paths = glob($threadsDir . '/*/*.htm*');
            $htmlPaths = array_merge($htmlPaths, $paths);
        }

        $htmlPaths = array_unique($htmlPaths);

        if (!$htmlPaths) {
            throw new \Exception('No threads found under given --file and --dir paths');
        }

        $threads = [];
        $threadNumber = 0;

        foreach ($htmlPaths as $path) {

            // $progress->setMessage(basename($path));
            $threadNumber++;
            $html = file_get_contents($path);
            $isMDvach = $this->isMDvachPage($html);
            $isArhivach = $this->looksLikeArchivachPage($html);

            try {
                // TODO: allow to choose parser manually
                if ($isMDvach) {
                    $thread = $this->mDvachThreadParser->extractThread($html, dirname($path));
                } elseif ($isArhivach) {
                    $thread = $this->arhivachThreadParser->extractThread($html, dirname($path));
                } else {
                    $thread = $this->dvachThreadParser->extractThread($html, dirname($path));
                }
            } catch (ThreadParseException $e) {
                if (!$skipBroken) {
                    throw $e;
                }

                $output->writeln(sprintf(
                    '%2d/%2d: %s - error: %s',
                    $threadNumber,
                    count($htmlPaths),
                    basename($path),
                    $e->getMessage()
                ));

                continue;
            }

            $threads[] = $thread;
            $output->write(sprintf(
                "%2d/%2d: %s [%d posts]\n",
                $threadNumber,
                count($htmlPaths),
                basename($path),
                count($thread->getPosts())
            ));
        }

        return $threads;
    }

    private function isMDvachPage(string $html): bool
    {
        // <title>#272705 - Программирование - М.Двач</title>
        // hacks hacks
        return (bool) preg_match('/<title>[^<>]*М.Двач/u', $html);
    }

    /**
     * Checks whether the file looks like archivach HTML page.
     */
    private function looksLikeArchivachPage(string $html): bool
    {
        // <link rel="shortcut icon" href="http://arhivach.org/favicon.ico">
        if (preg_match('~<link[^<>]+href="[^<>"]+arhivach\.org/favicon~', $html)) {
            return true;
        }

        // <link rel="canonical" href="http://arhivach.org/thread/266631/">
        if (preg_match('~<link[^<>]+rel="canonical"[^<>]+href="[^<>"]+arhivach\.org/~', $html)) {
            return true;
        }

        // <meta name="keywords" content="архивач, архива.ч, архив тредов,
        // архивы 2ch.hk, копипаста, сохраненные треды двача, сохранить тред,
        // имиджборд, archivach">
        if (preg_match('~<meta\b[^<>]+name="keywords"[^<>]+(архива\.ч)~', $html)) {
            return true;
        }

        return false;
    }

    private function getDefaultArhivachThreads(): array
    {
        return [
            25    => 'http://arhivach.cf/thread/25318/',
            79    => 'http://arhivach.cf/thread/191923/',
            '79b' => 'http://arhivach.cf/thread/193343/', // Нелегетимный 79-й тред
            80    => 'http://arhivach.cf/thread/197740/',
            81    => 'http://arhivach.cf/thread/204328/',
            82    => 'http://arhivach.cf/thread/213097/',
            83    => 'http://arhivach.cf/thread/216627/',
            84    => 'http://arhivach.cf/thread/224683/',
            85    => 'http://arhivach.cf/thread/233392/',
            86    => 'http://arhivach.cf/thread/245785/',
            87    => 'http://arhivach.cf/thread/249265/',
            88    => 'http://arhivach.cf/thread/254710/',
            89    => 'http://arhivach.cf/thread/261841/',
            90    => 'http://arhivach.cf/thread/266631/',
            91    => 'http://arhivach.cf/thread/282397/',
            92    => 'http://arhivach.cf/thread/282400/',
            93    => 'http://arhivach.cf/thread/302513/',
            94    => 'http://arhivach.cf/thread/302511/',
            95    => 'http://arhivach.cf/thread/312253/',
        ];
    }
}
