<?php

declare(strict_types=1);

namespace phpClub\Command;

use phpClub\BoardClient\ArhivachClient;
use phpClub\BoardClient\DvachClient;
use phpClub\Entity\Thread;
use phpClub\ThreadImport\ThreadImporter;
use phpClub\ThreadParser\ArhivachThreadParser;
use phpClub\ThreadParser\DvachThreadParser;
use phpClub\ThreadParser\Exception\ThreadParseException;
use phpClub\ThreadParser\MDvachThreadParser;
use phpClub\Util\FsUtil;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportThreadsCommand extends Command
{
    private ThreadImporter $threadImporter;
    private DvachClient $dvachApiClient;
    private ArhivachClient $arhivachClient;
    private DvachThreadParser $dvachThreadParser;
    private ArhivachThreadParser $arhivachThreadParser;
    private MDvachThreadParser $mDvachThreadParser;

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

        $this->addOption(
            'dump-urls',
            null,
            InputOption::VALUE_NONE,
            'Instead of importing threads, print their URLs, each on a new line. Can only be used together with --source/-s'
        );

        $this->addOption(
            'download-to',
            null,
            InputOption::VALUE_REQUIRED,
            'Instead of importing threads, download and save their HTML/JSON files into a given folder. Can only be used together with --source/-s'
        );

        $this->addOption(
            'no-images',
            null,
            InputOption::VALUE_NONE,
            'Do not download images when parsing threads'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $isDryRun = (bool) $input->getOption('dry-run');
        $noImages = (bool) $input->getOption('no-images');

        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $output->writeln('Parsing threads...');

        $threads = $this->getThreads($input, $output);

        $this->saveThreads($output, $isDryRun, $noImages, $threads);
    }

    /**
     * @throws \Exception
     *
     * @return Thread[]
     */
    private function getThreads(
        InputInterface $input, 
        OutputInterface $output
    ): array {
        $source = $input->getOption('source');
        $dumpUrls = (bool)$input->getOption('dump-urls');
        $downloadTo = $input->getOption('download-to');

        if ($downloadTo !== null && $source === null) {
            throw new \InvalidArgumentException("--save-html can be used only together with --source");
        }

        if ($dumpUrls && $source === null) {
            throw new \InvalidArgumentException("--dump-urls can be used only together with --source");
        }

        if ($source) {
            if ($source === '2ch-api') {
                if ($dumpUrls) {
                    $urls = $this->dvachApiClient->getAlivePhpThreads(DvachClient::RETURN_URLS);
                    $this->dumpUrls($output, $urls);
                    return [];
                }

                if ($downloadTo !== null) {
                    $jsons = $this->dvachApiClient->getAlivePhpThreads(DvachClient::RETURN_BODIES);
                    $this->saveBodies($output, $downloadTo, $jsons, 'json');
                    return [];
                }

                $threads = $this->dvachApiClient->
                    getAlivePhpThreads(DvachClient::RETURN_THREADS);
                // $this->saveThreads($output, $isDryRun, $threads);
                return $threads;
            }

            if ($source === 'arhivach') {
                $urls = $this->getDefaultArhivachThreads();
                if ($dumpUrls) {
                    $this->dumpUrls($output, $urls);
                    return [];
                }

                if ($downloadTo !== null) {
                    $htmls = $this->arhivachClient->downloadThreads($urls);
                    $this->saveBodies($output, $downloadTo, $htmls, 'html');
                    return [];
                }

                $threads = $this->arhivachClient->getPhpThreads($urls);
                // $this->saveThreads($output, $isDryRun, $threads);
                return $threads;
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

        if (is_array($fileGlobs)) {
            foreach ($fileGlobs as $glob) {
                $paths = glob($glob, GLOB_BRACE | GLOB_ERR);
                $htmlPaths = array_merge($htmlPaths, $paths);
            }
        }

        if (is_string($threadsDir)) {
            $paths = glob($threadsDir . '/*/*.htm*') ?: [];
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
            ++$threadNumber;
            $html = FsUtil::getContents($path);
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
        // $this->saveThreads($output, $isDryRun, $threads);
    }

    /**
     * @param Thread[] $threads 
     */
    private function saveThreads(
        OutputInterface $output, 
        bool $isDryRun, 
        bool $noImages,
        array $threads
    ): void { 
        if (!count($threads)) {
            $output->writeln("No threads to save");
            return;
        }

        if ($isDryRun) {
            $output->writeln("Dry run, don't save anything");
        } else {
            $output->writeln('Saving threads...');

            $progress = new ProgressBar($output, count($threads));
            $progress->setMessage('Thread saving progress');
            $progress->start();

            $this->threadImporter->import($threads, function () use (&$progress) {
                $progress->advance();
            }, $noImages);

            $progress->finish();
            $output->writeln('');
        }
    }

    private function dumpUrls(OutputInterface $output, iterable $urls): void 
    {
        foreach ($urls as $url) {
            $output->writeln($url);
        }
    }

    private function saveBodies(
        OutputInterface $output, 
        string $downloadTo, 
        array $files, 
        string $extension
    ): void {
        $fs = new Filesystem();

        foreach ($files as $threadName => $content) {
            $path = sprintf("%s/%s.%s", $downloadTo, $threadName, $extension);
            $output->writeln("Dump: $path");
            $fs->dumpFile($path, $content);
        }
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
            25 => getenv('ARHIVACH_DOMAIN') . '/thread/25318/',
            79 => getenv('ARHIVACH_DOMAIN') . '/thread/191923/',
            '79b' => getenv('ARHIVACH_DOMAIN') . '/thread/193343/', // Нелегетимный 79-й тред
            80 => getenv('ARHIVACH_DOMAIN') . '/thread/197740/',
            81 => getenv('ARHIVACH_DOMAIN') . '/thread/204328/',
            82 => getenv('ARHIVACH_DOMAIN') . '/thread/213097/',
            83 => getenv('ARHIVACH_DOMAIN') . '/thread/216627/',
            84 => getenv('ARHIVACH_DOMAIN') . '/thread/224683/',
            85 => getenv('ARHIVACH_DOMAIN') . '/thread/233392/',
            86 => getenv('ARHIVACH_DOMAIN') . '/thread/245785/',
            87 => getenv('ARHIVACH_DOMAIN') . '/thread/249265/',
            88 => getenv('ARHIVACH_DOMAIN') . '/thread/254710/',
            89 => getenv('ARHIVACH_DOMAIN') . '/thread/261841/',
            90 => getenv('ARHIVACH_DOMAIN') . '/thread/266631/',
            91 => getenv('ARHIVACH_DOMAIN') . '/thread/282397/',
            92 => getenv('ARHIVACH_DOMAIN') . '/thread/282400/',
            93 => getenv('ARHIVACH_DOMAIN') . '/thread/302513/',
            94 => getenv('ARHIVACH_DOMAIN') . '/thread/302511/',
            95 => getenv('ARHIVACH_DOMAIN') . '/thread/312253/',
        ];
    }
}
