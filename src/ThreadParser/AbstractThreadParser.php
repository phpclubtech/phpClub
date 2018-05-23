<?php

declare(strict_types=1);

namespace phpClub\ThreadParser;

use Symfony\Component\DomCrawler\Crawler;
use phpClub\Entity\File;
use phpClub\Entity\Post;
use phpClub\Entity\Thread;
use phpClub\ThreadParser\MarkupConverter;
use phpClub\Util\DOMUtil;

abstract class AbstractThreadParser
{
    /**
     * @var DateConverter
     */
    protected $dateConverter;

    /**
     * @var MarkupConverter 
     */
    protected $markupConverter;

    /**
     * @param DateConverter $dateConverter
     */
    public function __construct(DateConverter $dateConverter)
    {
        $this->markupConverter = new MarkupConverter;
        $this->dateConverter = $dateConverter;
    }

    abstract protected function getPostsXPath(): string;

    abstract protected function getIdXPath(): string;

    abstract protected function getTitleXPath(): string;

    abstract protected function getAuthorXPath(): string;

    abstract protected function getDateXPath(): string;

    abstract protected function getTextXPath(): string;

    abstract protected function getFilesXPath(): string;

    abstract protected function extractFile(Crawler $fileNode): File;

    /**
     * @param string $threadHtml
     * @param string $threadPath
     *
     * @return Thread
     */
    public function extractThread(string $threadHtml, string $threadPath = ''): Thread
    {
        $hasCloudflareEmails = strstr($threadHtml, '__cf_email__');
        $threadCrawler = new Crawler($threadHtml);

        $postsXPath = $this->getPostsXPath();
        $postNodes = $threadCrawler->filterXPath($postsXPath);

        if (!count($postNodes)) {
            throw new ThreadParseException('Post nodes not found');
        }

        $firstPost = $postNodes->first();

        $thread = new Thread($this->extractId($firstPost));

        $extractPost = function (Crawler $postNode) use ($thread, $threadPath, $hasCloudflareEmails) {

            if ($hasCloudflareEmails) {
                $postNode = $this->restoreCloudflareEmails($postNode);
            }

            $post = (new Post($this->extractId($postNode)))
                ->setTitle($this->extractTitle($postNode))
                ->setAuthor($this->extractAuthor($postNode))
                ->setDate($this->extractDate($postNode))
                ->setText($this->extractText($postNode))
                ->setThread($thread);

            if (!$this->isThreadWithMissedFiles($thread)) {
                $files = $this->extractFiles($postNode, $threadPath);
                foreach ($files as $file) {
                    $post->addFile($file);
                }
            }

            $thread->addPost($post);
        };

        $postNodes->each($extractPost);

        $this->assertThatPostIdsAreUnique($thread);

        return $thread;
    }

    protected function assertThatPostIdsAreUnique(Thread $thread)
    {
        $ids = [];
        $posts = $thread->getPosts();

        foreach ($posts as $post) {
            $id = $post->getId();

            if (array_key_exists($id, $ids)) {
                throw new ThreadParseException("In thread {$thread->getId()} there is more than one post with id {$id}");
            }

            $ids[$id] = true;
        }
    }

    /**
     * @param Crawler $postNode
     *
     * @return int
     */
    protected function extractId(Crawler $postNode): int
    {
        $idXPath = $this->getIdXPath();
        $idNode = $postNode->filterXPath($idXPath);

        if (!count($idNode)) {
            throw new ThreadParseException("Unable to parse post id, HTML: {$this->getOuterHtml($postNode)}");
        }

        $postId = preg_replace('/[^\d]+/', '', $idNode->text());

        return (int) $postId;
    }

    /**
     * @param Crawler $postNode
     *
     * @return string
     */
    protected function extractTitle(Crawler $postNode): string
    {
        $titleXPath = $this->getTitleXPath();
        $titleNode = $postNode->filterXPath($titleXPath);

        if (!count($titleNode)) {
            return '';
        }

        return trim($titleNode->text());
    }

    /**
     * @param Crawler $postNode
     *
     * @return string
     */
    protected function extractAuthor(Crawler $postNode): string
    {
        $authorXPath = $this->getAuthorXPath();
        $authorNode = $postNode->filterXPath($authorXPath);

        if (!count($authorNode)) {
            throw new ThreadParseException("Unable to parse post author, HTML: {$this->getOuterHtml($postNode)}");
        }

        return $authorNode->text();
    }

    /**
     * @param Crawler $postNode
     *
     * @return \DateTimeImmutable
     */
    protected function extractDate(Crawler $postNode): \DateTimeInterface
    {
        $dateXPath = $this->getDateXPath();
        $dateNode = $postNode->filterXPath($dateXPath);

        if (!count($dateNode)) {
            throw new ThreadParseException("Unable to parse post date, HTML: {$this->getOuterHtml($postNode)}");
        }

        return $this->dateConverter->toDateTime($dateNode->text());
    }

    /**
     * @param Crawler $postNode
     *
     * @return string
     */
    protected function extractText(Crawler $postNode): string
    {
        $textXPath = $this->getTextXPath();
        $blockquoteNode = $postNode->filterXPath($textXPath);

        if (!count($blockquoteNode)) {
            throw new ThreadParseException("Unable to parse post text, HTML: {$this->getOuterHtml($postNode)}");
        }

        // $textNode is an iterable
        $blockquoteDomNode = $blockquoteNode->getNode(0);
        $this->markupConverter->transformIterable($blockquoteDomNode->childNodes);

        return trim($blockquoteNode->html());
    }

    /**
     * @param Crawler $postNode
     * @param Post    $post
     * @param string  $threadPath
     *
     * @return File[]
     */
    protected function extractFiles(Crawler $postNode, string $threadPath): array
    {
        $filesXPath = $this->getFilesXPath();
        $fileNodes = $postNode->filterXPath($filesXPath);

        $extractFile = function (Crawler $fileNode) use ($threadPath) {
            $file = $this->extractFile($fileNode);

            if ($threadPath && !filter_var($file->getPath(), FILTER_VALIDATE_URL)) {
                $file->updatePaths(
                    $threadPath . '/' . basename($file->getPath()),
                    $threadPath . '/' . basename($file->getThumbPath())
                );
            }

            return $file;
        };

        return $fileNodes->each($extractFile);
    }

    protected function isThreadWithMissedFiles(Thread $thread): bool
    {
        // 345388 - Thread #15 (Google cache)
        $threadsWithMissedFiles = ['345388'];

        return in_array($thread->getId(), $threadsWithMissedFiles, $strict = true);
    }

    protected function getOuterHtml(Crawler $post)
    {
        $node = $post->getNode(0);
        return DOMUtil::getOuterHtml($node);
    }

    /**
     * Removes cloudflare-encoded emails from post body
     *
     * Returns a new Crawler with a copy of DOM tree.
     */
    public static function restoreCloudflareEmails(Crawler $post): Crawler
    {
        //    Cloudflare replaces email with code (formatted): 
        //    
        //    <a class="__cf_email__" 
        //        href="http://www.cloudflare.com/email-protection" 
        //        data-cfemail="50243835373c253510243f223d31393c7e3f2237">[email&nbsp;protected]</a>
        //        <script type="text/javascript">
        //          /* <![CDATA[ */
        //          (function(){try{
        //              var s,a,i,j,r,c,l,b=document.getElementsByTagName("script");
        //              l=b[b.length-1].previousSibling;
        //              a=l.getAttribute('data-cfemail');
        //              if(a){
        //                  s='';
        //                  r=parseInt(a.substr(0,2),16);
        //                  for(j=2;a.length-j;j+=2){
        //                      c=parseInt(a.substr(j,2),16)^r;
        //                      s+=String.fromCharCode(c);
        //                  }
        //                  s=document.createTextNode(s);
        //                  l.parentNode.replaceChild(s,l);
        //              }
        //          }catch(e){}})();
        //          /* ]]> */
        //        </script>
        //        
        //    We replace it with decoded email
        
        assert($post->count() == 1);
        $postNode = $post->getNode(0);

        $replacement = DOMUtil::transformDomTree($postNode, function (\DOMNode $node) {

            $nodeName = strtolower($node->nodeName);

            if ($nodeName == 'a') {
                $class = $node->getAttribute('class');
                if ($class != '__cf_email__') {
                    return $node;
                }

                $encodedEmail = $node->getAttribute('data-cfemail');
                if (!$encodedEmail) {
                    return $node;
                }

                $email = self::decodeCfEmail($encodedEmail);

                $textNode = $node->ownerDocument->createTextNode($email);
                return $textNode;
            }

            if ($nodeName == 'script') {
                if (false === strstr($node->textContent, 'c=parseInt(a.substr(j,2),16)^r')) {
                    return $node;
                }

                // Remove script
                return null;
            }

            return $node;     
        });

        $newCrawler = new Crawler;
        $newCrawler->addNodes($replacement);

        return $newCrawler;
    }  

    /**
     * Decodes emails from data-cfemail attribute:
     *
     * 123456abcdef => some@example.com
     */
    public static function decodeCfEmail($cfeString)
    {
        if (!preg_match("/^([0-9a-fA-F]{2}){2,}$/", $cfeString)) {
            throw new \Exception("Invalid data-cfemail string: '$cfeString'");
        }

        $result = '';
        $key = hexdec(mb_substr($cfeString, 0, 2));
        $length = mb_strlen($cfeString);

        for ($i=2; $i < $length; $i+=2) { 
            $byte = hexdec(mb_substr($cfeString, $i, 2)) ^ $key;
            $result .= chr($byte);
        }

        return $result;
    }
}
