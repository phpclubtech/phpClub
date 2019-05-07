<?php

namespace phpClub\ThreadParser\Internal;

use phpClub\Util\DOMUtil;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @author codedokode
 */
class CloudflareEmailDecoder
{
    /**
     * Decodes emails from data-cfemail attribute:.
     *
     * 123456abcdef => some@example.com
     */
    public function decodeCfEmail($cfeString): string
    {
        if (!preg_match('/^([0-9a-fA-F]{2}){2,}$/', $cfeString)) {
            throw new \Exception("Invalid data-cfemail string: '$cfeString'");
        }

        $result = '';
        $key = hexdec(mb_substr($cfeString, 0, 2));
        $length = mb_strlen($cfeString);

        for ($i = 2; $i < $length; $i += 2) {
            $byte = hexdec(mb_substr($cfeString, $i, 2)) ^ $key;
            $result .= chr($byte);
        }

        return $result;
    }

    public function hasCloudflareEmails(string $html): bool
    {
        return false !== strstr($html, '__cf_email__');
    }

    /**
     * Removes cloudflare-encoded emails from post body.
     *
     * Returns a new Crawler with a copy of DOM tree.
     */
    public function restoreCloudflareEmails(Crawler $post): Crawler
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

        $replacement = DOMUtil::transformDomTree($postNode, function ($node) {
            /** @var \DOMElement $node */
            $nodeName = strtolower($node->nodeName);

            if ($nodeName === 'a') {
                $class = $node->getAttribute('class');
                if ($class !== '__cf_email__') {
                    return $node;
                }

                $encodedEmail = $node->getAttribute('data-cfemail');
                if (!$encodedEmail) {
                    return $node;
                }

                $email = $this->decodeCfEmail($encodedEmail);

                return $node->ownerDocument->createTextNode($email);
            }

            if ($nodeName === 'script') {
                if (false === strstr($node->textContent, 'c=parseInt(a.substr(j,2),16)^r')) {
                    return $node;
                }

                // Remove script
                return null;
            }

            return $node;
        });

        $newCrawler = new Crawler();
        $newCrawler->addNodes($replacement);

        return $newCrawler;
    }
}
