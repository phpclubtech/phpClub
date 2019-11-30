<?php

declare(strict_types=1);

namespace Tests\ThreadParser;

use phpClub\Entity\Post;
use phpClub\Entity\Thread;
use phpClub\ThreadParser\ArhivachThreadParser;
use phpClub\ThreadParser\DvachThreadParser;
use phpClub\ThreadParser\MDvachThreadParser;
use phpClub\Util\DOMUtil;
use phpClub\Util\FsUtil;
use Symfony\Component\DomCrawler\Crawler;
use Tests\AbstractTestCase;

/**
 * Test thread files parsing.
 *
 * For every thread, there is a number of "samples" that
 * look like this:
 *
 * ["text", 123456, "some text"]
 *
 * It means that post 123456 contains text "some text".
 */
class CommonParserTest extends AbstractTestCase
{
    /** @var array [path => Thread] */
    private static $threadCache = [];

    /**
     * @dataProvider provideDvachSamples
     */
    public function testDvachParser(string $threadPath, string $type, int $postId, string $content)
    {
        $thread = $this->getParsedThreadCached($threadPath, DvachThreadParser::class);
        $this->findSampleInThread($thread, $type, $postId, $content, 'dvach');
    }

    /**
     * @dataProvider provideArhivachSamples
     */
    public function testArhivachParser(string $threadPath, string $type, int $postId, string $content)
    {
        $thread = $this->getParsedThreadCached($threadPath, ArhivachThreadParser::class);
        $this->findSampleInThread($thread, $type, $postId, $content, 'arhivach');
    }

    public function provideDvachSamples(): array
    {
        $dir = dirname(__DIR__);

        $threads = [
            'dvach/2.html'  => [$dir . '/Fixtures/dvach/2.html', $this->getDvachThread2Samples()],
            'dvach/30.html' => [$dir . '/Fixtures/dvach/30.html', $this->getDvachThread30Samples()],
            'dvach/34.html' => [$dir . '/Fixtures/dvach/34.html', $this->getDvachThread34Samples()],
            'dvach/82.html' => [$dir . '/Fixtures/dvach/82.html', $this->getDvachThread82Samples()],
        ];

        $data = [];

        foreach ($threads as $threadData) {
            list($threadPath, $samples) = $threadData;

            foreach ($samples as $sample) {
                list($type, $postId, $content) = $sample;
                $data[] = [$threadPath, $type, $postId, $content];
            }
        }

        return $data;
    }

    public function provideArhivachSamples(): array
    {
        $dir = dirname(__DIR__);

        $threads = [
            'arhivach/25.html' => [$dir . '/Fixtures/arhivach/25.html', $this->getDvachThread25Samples()],
            'arhivach/90.html' => [$dir . '/Fixtures/arhivach/90.html', $this->getDvachThread90Samples()],
        ];

        $data = [];

        foreach ($threads as $threadData) {
            list($threadPath, $samples) = $threadData;

            foreach ($samples as $sample) {
                list($type, $postId, $content) = $sample;
                $data[] = [$threadPath, $type, $postId, $content];
            }
        }

        return $data;
    }

    private function getDvachThread2Samples()
    {
        // type, postId, content, comment
        return [
            ['text', 247752, 'Наверняка тут есть те, кто хочет попробовать'],
            ['text', 247752, 'не устраиваем холивар по поводу нужен ли PHP или нет', 'spoiler'],
            ['text', 247752, 'Сайт опять упал?', 'bold'],
            ['text', 247752, 'http://rghost.net/41915862', 'link'],
            ['text', 247752, 'В общем, хватит разговоров'],

            // Different markup
            ['text', 247754, 'function makeMeFeelGood', 'code'],
            ['text', 247821, 'мимомехматоотличник', 'italic'],
            ['text', 247890, 'Да ты издеваешься!', 'quote'],
            ['text', 258742, 'В-четвёртых', 'underline'],
            ['text', 254899, '2', 'sup'],

            // Last post
            ['text', 268548, 'Тред почему-то тонет'],

            ['trip', 247752, '!xnn2uE3AU.'],
            ['trip', 247778, ''],
            ['trip', 248954, '!WgiiL9n8io'],

            ['author', 248954, 'Stockholm-кун'],
            ['author', 248949, 'Аноним'],
            ['author', 258281, 'sage'],

            ['email', 250712, 'sage'],
            ['email', 258281, 'sage'],

            ['subject', 258281, 'sage'],

            // Pictures
            ['image', 247752, '/pr/src/1361972370522.png'],
            ['thumb', 247752, '/pr/thumb/1361972370522s.gif'],
            ['size', 247752, '500x500'],

            ['image', 267620, '/pr/src/1366510503127.jpg'],
            ['thumb', 267620, '/pr/thumb/1366510503127s.gif'],
            ['size', 267620, '682x1024'],

            ['youtube-pic', 258711, 'GpkC7fcr8jM'],

            ['date', 247752, '2013-02-27T16:39:30'],
            ['date', 268548, '2013-04-23T06:14:03'],
        ];
    }

    private function getDvachThread25Samples()
    {
        // type, postId, content, comment
        return [
            ['text', 360376, 'А давайте в этом ИТТ треде будем изучать PHP'],
            ['text', 360376, 'Если я решу твой учебник, я смогу'],
            ['text', 360378, 'function bakeCookies(...)', 'code'],
            ['text', 360791, 'ОП СПУСТЯ МЕСЯЦ Я СОЗРЕЛ'],

            ['trip', 360376, '!xnn2uE3AU.'],

            ['date', 360376, '2014-06-09T13:39:31'],
            ['date', 360791, '2014-06-11T01:57:46'],

            ['subject', 360376, 'Клуб изучающих PHP #25'],
            ['subject', 360378, 'Пиши верно'],
            ['subject', 360572, 'регулярки'],

            ['author', 360381, 'Аноним'],

            ['image', 360376, '1402306771864.png'],
        ];
    }

    private function getDvachThread30Samples()
    {
        // type, postId, content, comment
        return [
            // This thread has two different date formats
            ['text', 377570, 'Всем привет'],
            ['text', 377570, 'В общем, давайте начинать'],

            ['text', 378308, 'if ($humanSum>$robotSum) {', 'code'],
            ['text', 378643, 'Joomla, Webasyst, MODX', 'quote'],
            ['text', 378643, 'контент-менеджер', 'spoiler'],
            ['text', 381133, 'Поздно ли учить PHP в 26?'],

            // last post
            ['text', 383389, 'Спасибо. Я что-то не догадался посмотреть на сообщение от PHP'],

            ['subject', 377570, 'Клуб любителей изучать PHP #30'],
            ['subject', 377571, 'Пиши красиво'],
            ['subject', 379692, 'Помогач-вопросач!'],

            ['trip', 377570, '!xnn2uE3AU.'],

            ['author', 378075, 'Аноним'],
            ['author', 382151, '`'],

            ['date', 377570, '2014-08-13T03:57:04'],
            ['date', 381952, '2014-08-27T17:26:28'],

            ['image', 377570, '/pr/src/377570/1407887824895.png'],
            ['thumb', 377570, '/pr/thumb/377570/1407887824895s.gif'],
            ['size', 377570, '500x500'],

            ['image', 381751, '/pr/src/377570/14090672526641.png'],
            ['thumb', 381751, '/pr/thumb/377570/14090672526641s.jpg'],
            ['size', 381751, '675x36'],
        ];
    }

    private function getDvachThread34Samples()
    {
        return [
            // pr-thread-34
            ['text', 395787, 'В этом ИТТ треде мы изучаем PHP'],
            ['text', 395787, 'В общем, давайте начинать уже'],
            ['text', 396934, 'миморубист', 'italic'],
            ['text', 396934, '<html>'],
            ['text', 396936, '>>396926'],
            ['text', 396942, 'пытается заставить работать пхп у клиента', 'quote'],
            ['text', 396948, 'не знаю даже, что в гугле забивать', 'spoiler'],

            // last post
            ['text', 400041, 'Создавал таблицу через MySQL Workbench'],

            ['subject', 395787, 'рнр тред номер 34'],
            ['subject', 395792, 'Клуб изучающих PHP'],

            ['author', 395792, '!ОП'],
            ['author', 395801, 'Аноним'],
            ['author', 396704, 'Воннаби'],

            ['trip', 396837, '!3MOm6dO3V2'],
            ['trip', 396839, '!xnn2uE3AU.'],

            ['email', 396934, 'sage'],
            ['email', 397230, 'stremitel007@rambler.ru'],

            ['date', 395787, '2014-10-15T12:18:40'],
            ['date', 400041, '2014-10-29T07:39:43'],

            ['image', 396704, '14136508968842.jpg'],
            ['thumb', 396704, '14136508968842s.jpg'],
            ['size', 396704, '992x1200'],
        ];
    }

    private function getDvachThread82Samples()
    {
        return [
            // pr-thread-82
            ['text', 864640, 'Добро пожаловать в наш уютный тред.'],
            ['text', 864640, 'не надо тратить время на поиск заказов и переговоры с неадекватными заказчиками'],
            ['text', 864640, 'Высказывайтесь одним большим постом', 'bold'],
            ['text', 864763, 'comment = none', 'italic'],
            ['text', 865009, 'Изучаю с 1 октября', 'spoiler'],
            ['text', 866928, 'Есть сервер на джаве и клиент на пыхе', 'quote'],

            // last post
            ['text', 884791, 'Если я кого-то пропустил'],

            ['date', 864640, '2016-10-27T15:58:45'],
            ['date', 884791, '2016-11-30T09:58:33'],

            ['subject', 864640, 'Клуб изучающих PHP 82'],
            ['subject', 884783, 'https://bitbucket.org/learningacc/file-downloader/'],

            ['author', 864640, 'Аноним'],
            ['author', 865009, 'Satosi'],

            ['email', 870894, 'sage'],

            ['image-name', 864640, 'cat-sad.jpg'],
            ['image', 864640, '14775731253722.jpg'],
            ['thumb', 864640, '14775731253722s.jpg'],
            ['size', 864640, '1024x768'],

            ['image-name', 869219, 'zce-php-engineer-logo-l.jpg'],
            ['image', 869219, '14781898497020.jpg'],
            ['thumb', 869219, '14781898497020s.jpg'],
            ['size', 869219, '432x402'],
        ];
    }

    private function getDvachThread90Samples(): array
    {
        return [
            ['text', 1000416, 'Добро пожаловать в наш уютный'],
            ['text', 1000416, '>>988868'],
            ['text', 1000535, 'посоветуйте редактор текстовый для убунту'],
            ['text', 1000566, 'А почему оно должно меняться?'],
            ['text', 1018009, 'насколько всё ужасно', 'spoiler'],

            ['subject', 1000416, 'Клуб изучающих PHP 90'],
            ['subject', 1005550, 'https://github.com/Merkalov/Students'],

            ['author', 1000416, 'Аноним'],

            ['date', 1000416, '2017-06-03T14:06:06'],
            ['date', 1018049, '2017-07-07T15:18:58'],

            ['email', 1019198, 'sage'],

            ['image', 1000416, '14964879662911.jpg'],
            ['thumb', 1000416, '14964879662911s.jpg'],
            ['image-name', 1000416, 'cat-cafe-osaka.jpg'],
            ['size', 1000416, '1024x683'],
        ];
    }

    private function getParsedThreadCached(string $threadPath, string $type): Thread
    {
        $cacheKey = $type . ':' . $threadPath;

        if (!array_key_exists($cacheKey, self::$threadCache)) {
            $thread = $this->parseThread($threadPath, $type);
            self::$threadCache[$cacheKey] = $thread;
        }

        $thread = self::$threadCache[$cacheKey];

        // TODO: make a clone
        return $thread;
    }

    private function parseThread(string $threadPath, string $parserClass): Thread
    {
        if ($parserClass == DvachThreadParser::class) {
            $parser = $this->getContainer()->get(DvachThreadParser::class);
        } elseif ($parserClass == ArhivachThreadParser::class) {
            $parser = $this->getContainer()->get(ArhivachThreadParser::class);
        } elseif ($parserClass == MDvachThreadParser::class) {
            $parser = $this->getContainer()->get(MDvachThreadParser::class);
        } else {
            throw new \InvalidArgumentException('Invalid thread parser: ' . $parserClass);
        }

        $html = FsUtil::getContents($threadPath);
        $thread = $parser->extractThread($html, $threadPath);

        return $thread;
    }

    private function findSampleInThread(
        Thread $thread,
        string $type,
        int $postId,
        string $content,
        string $parser
    ) {
        $post = $this->findPostById($thread, $postId);
        $this->assertNotEmpty($post, "Cannot find post $postId in thread");

        switch ($type) {
            case 'text':
                // Post text must contain this text
                $crawler = new Crawler($post->getText());
                $postText = DOMUtil::getTextFromCrawler($crawler);

                $this->assertStringContainsStringIgnoringSpaces(
                    $content,
                    $postText,
                    "Cannot find string '$content' in post $postId"
                );

                break;

            // Both trip code and author are stored in the same field
            case 'trip':
                // TODO: Cannot be parsed reliably
                break;

            case 'author':

                $this->assertEqualsIgnoringSpaces(
                    $content,
                    strval($post->getAuthor()),
                    "Cannot find $type '$content' inside post $postId"
                );

                break;

            case 'email':

                // TODO: Is not parsed reliably
                break;

                $this->assertEqualsIgnoringSpaces(
                    $content,
                    strval($post->getEmail()),
                    "Cannot find email '$content' inside post $postId"
                );

                break;

            case 'subject':
                $this->assertStringContainsStringIgnoringSpaces(
                    $content,
                    strval($post->getTitle()),
                    "Cannot find title '$content' in post $postId"
                );

                break;

            case 'date':
                $tzMoscow = new \DateTimeZone('Europe/Moscow');
                $date = clone $post->getDate();
                $dateMoscow = $date->setTimezone($tzMoscow);

                $this->assertEquals(
                    $content,
                    $dateMoscow->format('Y-m-d\TH:i:s'),
                    "Post $postId date doesn't match value '$content'"
                );

                break;

            case 'image':

                if ($parser == 'arhivach') {
                    // TODO: not yet ready
                    break;
                }

                $content = $this->removeDirs($content);
                $paths = [];

                foreach ($post->getFiles() as $file) {
                    $paths[] = $this->removeDirs($file->getPath());
                }

                $this->assertContains(
                    $content,
                    $paths,
                    "Cannot find image '$content' in post $postId"
                );

                break;

            case 'thumb':

                if ($parser == 'arhivach') {
                    // TODO: not yet ready
                    break;
                }

                $content = $this->removeDirs($content);
                $paths = [];

                foreach ($post->getFiles() as $file) {
                    $paths[] = $this->removeDirs($file->getThumbPath());
                }

                $this->assertContains(
                    $content,
                    $paths,
                    "Cannot find thumb path '$content' in post $postId"
                );

                break;

            case 'size':

                $sizes = [];
                foreach ($post->getFiles() as $file) {
                    $sizes[] = $file->getWidth() . 'x' . $file->getHeight();
                }

                $this->assertContains(
                    $content,
                    $sizes,
                    "Cannot find image of size '$content' in post $postId"
                );

                break;

            case 'image-name':

                // TODO: Doesn't work yet
                break;

                $names = [];

                foreach ($post->getFiles() as $file) {
                    $names[] = $file->getName();
                }

                $this->assertContains(
                    $content,
                    $names,
                    "Cannot find image with name '$content' in post $postId"
                );

                break;

            case 'youtube-pic':
                // TODO: Not supported yet
                break;

            default:
                throw new \Exception("Unknown sample type '$type'");
        }
    }

    private function findPostById(Thread $thread, int $postId): Post
    {
        foreach ($thread->getPosts() as $post) {
            if ($post->getId() == $postId) {
                return $post;
            }
        }

        throw new \Exception("Cannot find post $postId in thread");
    }

    private function assertStringContainsStringIgnoringSpaces(string $needle, string $haystack, string $message = '')
    {
        $needle = $this->normalizeSpaces($needle);
        $haystack = $this->normalizeSpaces($haystack);

        $this->assertStringContainsString($needle, $haystack, $message);
    }

    private function assertEqualsIgnoringSpaces(string $expected, string $actual, string $message = '')
    {
        $expected = $this->normalizeSpaces($expected);
        $actual = $this->normalizeSpaces($actual);

        $this->assertEquals($expected, $actual, $message);
    }

    private function normalizeSpaces(string $value): string
    {
        return trim(preg_replace("/\s+/", ' ', $value));
    }

    private function removeDirs(string $path): string
    {
        $parts = explode('/', $path);

        return array_pop($parts);
    }
}
