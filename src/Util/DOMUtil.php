<?php 

declare(strict_types=1);

namespace phpClub\Util;

use Symfony\Component\DomCrawler\Crawler;

class DOMUtil
{
    /**
     * Creates a DOM fragment from string. Doesn't accept HTML that contains
     * <body> or <html> tags.
     * 
     * @param string $html HTML string without html or body tag, 
     *                     e.g. 'Hello <em>world</em>'
     */
    public static function createFragment(string $html):\DOMDocumentFragment
    {
        $ignoreHtmlErrors = true;

        $doc = new \DOMDocument;

        // A hack to force utf-8 charset
        $fullHtml = '<html><meta charset="utf-8"><body>' . $html . '</body></html>';
        // $fullHtml = '<?xml encoding="utf-8" ? >' . $html;

        if ($ignoreHtmlErrors) {
            $oldValue = libxml_use_internal_errors(true);
            $doc->loadHtml($fullHtml);
            $htmlErrors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($oldValue);
        } else {
            $doc->loadHtml($fullHtml);
        }

        $doc->normalizeDocument();

        $html = $doc->documentElement;
        $body = null;
        foreach ($html->childNodes as $node) {
            if (mb_strtolower($node->nodeName) == 'body') {
                $body = $node;
                break;
            }
        }

        assert(!!$body);

        $fragment = $doc->createDocumentFragment();
        $child = $body->firstChild;
        while ($child) { 
            // Save next child reference as it changes after moving
            $next = $child->nextSibling;
            $fragment->appendChild($child);
            $child = $next;
        }

        return $fragment;
    }

    /**
     * Creates a DOM node from string. Doesn't accept HTML code with
     * <body> or <html> tags.
     * 
     * @param string $html HTML string without html or body tag, 
     *                     containg a single DOM node e.g. '<em>test</em>'
     */
    public static function createNode(string $html):\DOMNode
    {
        $fragment = self::createFragment($html);
        $node = $fragment->firstChild;

        if ($node->nextSibling) {
            throw new \InvalidArgumentException("HTML must contain a single node, second node ({$node->nodeName}) found");
        }

        /* 
            If we return $node now, there will be an error 
            "Couldn't fetch DOMElement. Node no longer exists"
            when you try to use it probably because
            $fragment is destroyed along with its child nodes
            when leaving function.
        
            So we remove $node from the fragment.
        */

        $fragment->removeChild($node);

        return $node;
    }

    /**
     * Returns HTML code representing the node
     */
    public static function getOuterHtml(\DOMNode $node): string
    {
        return $node->ownerDocument->saveHTML($node);
    }

    /**
     * Transforms a DOM tree by passing each node to $transformer callback. 
     * The callback must return:
     *
     * - either the same node unchanged
     * - or modified node
     * - or \DocumentFragment with new nodes to replace given node
     * - or null to indicate that the node must be removed
     *
     * Callback can modify passed nodes and doesn't need to clone
     * them before modifying. Callback can add or remove children from node. 
     * Callback will get all of the nodes including text and comment nodes.
     * 
     * Walks the DOM tree from bottom to top.
     *
     * Returns an \DOMNode[] that can be empty or have more than one node.
     */
    public static function transformDomTree(\DOMNode $root, callable $transformer): array
    {
        $copy = $root->cloneNode(true);
        $replacement = self::transformNodeRecursively($copy, $transformer);
        return $replacement;
    }

    private static function transformNodeRecursively(\DOMNode $node, callable $transformer): array
    {
        if ($node->hasChildNodes()) {

            $newChildren = [];

            while ($node->firstChild) {
                $child = $node->firstChild;
                $node->removeChild($child);

                $replacement = self::transformNodeRecursively($child, $transformer);
                foreach ($replacement as $newNode) {
                    $newChildren[] = $newNode;
                }
            }

            foreach ($newChildren as $newChild) {
                $node->appendChild($newChild);
            }
        }

        $replacement = $transformer($node);

        if ($replacement === null) {
            // Transformer wants to remove the node
            return [];
        } elseif ($replacement === $node) {
            // Leave the same node
            return [$replacement];
        } elseif ($replacement instanceof \DOMDocumentFragment) {
            // Use these as replacement
            $nodes = [];
            while ($replacement->firstChild) {
                $child = $replacement->firstChild;
                $replacement->removeChild($child);
                $nodes[] = $child;
            }

            return $nodes;
        } elseif ($replacement instanceof \DOMNode) {
            return [$replacement];
        } else {
            throw new \Exception("Transformer must return either null, or \DOMNode, or \DOMDocumentFragment");
        }
    }

    public static function hasClass(string $classList, string $class)
    {
        $classes = preg_split("/\s+/", trim($classList));
        return in_array($class, $classes, true);
    }

    public static function getTextFromCrawler(Crawler $crawler): string
    {
        // We get an exception trying to get text from empty Crawler
        if (!$crawler->count()) {
            return '';
        }

        return $crawler->text();
    }
    
}

