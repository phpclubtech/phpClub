<?php

declare(strict_types=1);

namespace phpClub\ThreadParser;

use InvalidArgumentException;
use phpClub\ThreadParser\Exception\InvalidMarkupException;
use phpClub\Util\DOMUtil;

/**
 * Validates and transforms HTML markup inside post body.
 */
class MarkupConverter
{
    /**
     * A whitelist of tags that can be used, and a whitelist of
     * allowed attributes, e.g.:.
     *
     * <strong>text</strong>
     * <font color="...">...</font>
     */
    private static array $allowedTags = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'em' => [],
        'sub' => [],
        'sup' => [],
        'pre' => [],
        'code' => [],
        'font' => ['color'],
        'span' => ['class'],
        // There can also be attributes like onmouseover, onmouseout, onclick
        // but we strip them before validating
        'a' => ['href', 'rel', 'target', 'class',
            'data-thread', 'data-num', ],
    ];

    /**
     * Classes that can be used with spans, e.g.
     * <span class="spoiler">...</span>.
     */
    private static array $allowedSpanClasses = [
        'unkfunc' => true, // a quote
        's' => true, // strike-out
        'u' => true, // underline
        'o' => true, // overline
        'spoiler' => true,
        'code_container' => true,
        'code_line' => true,
        'pomyanem' => true, // message about a banner user from moderator
    ];

    /**
     * Classes that can be used on <a> tag.
     */
    private static array $allowedAClasses = [
        'post-reply-link' => true,
    ];

    /**
     * Allows additional tags that are present in arhivach code.
     */
    private bool $isArhivachMode = false;

    public function __construct(bool $isArhivachMode = false)
    {
        $this->isArhivachMode = $isArhivachMode;
    }

    /**
     * Validates and transforms post HTML markup. Throws an exception if
     * unexpected HTML tag is found. This might mean that either
     * a new type of markup was added (and this code needs to be updated)
     * or there is an error in a parser and it captured extra markup when
     * extracting the post body (and you have to fix the parser).
     *
     * See docs for types of allowed HTML markup in a post.
     *
     * You can pass either single DOM node, or a DOMDocumentFragment with
     * several nodes.
     *
     * The method modifies passed DOM tree in place.
     *
     * It returns a modified DOM node or null if there is nothing left and
     * all content was removed.
     *
     * @param \DOMDocumentFragment|\DOMElement|\DOMText|\DOMNode $body
     */
    public function transformBody($body): ?\DOMNode
    {
        $type = $body->nodeType;

        if ($type == XML_TEXT_NODE) {
            // Nothing to do
            return $body;
        }

        if ($type == XML_DOCUMENT_FRAG_NODE) {
            /* @var \DOMDocumentFragment $body */
            $this->transformChildren($body);

            return $body;
        }

        if ($type === XML_ELEMENT_NODE) {
            /** @var \DOMElement $body */
            $keep = $this->transformElementRecursively($body);

            return $keep ? $body : null;
        }

        throw new InvalidArgumentException("Invalid DOM Node type; only text, element or document fragment node is allowed, given type='$type'");
    }

    /**
     * Processes all child nodes of a node, but doesn't do anything
     * with the node itself.
     *
     * Can modify or remove children.
     *
     * @param \DOMNode $node iterable List of \DOMNodes
     */
    public function transformChildren(\DOMNode $node): void
    {
        // Delay removing children not to break loop over them
        $toRemove = [];

        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $keep = $this->transformElementRecursively($child);
                if (!$keep) {
                    $toRemove[] = $child;
                }
            }
        }

        foreach ($toRemove as $child) {
            $node->removeChild($child);
        }
    }

    /**
     * Validates an element and its descendants. Returns:.
     *
     * - true to keep element
     * - false to remove it
     */
    private function transformElementRecursively(\DOMElement $node): bool
    {
        $name = mb_strtolower($node->nodeName);

        if (!array_key_exists($name, self::$allowedTags)) {
            throw new InvalidMarkupException("Tag '$name' is not allowed in markup");
        }

        if ($name === 'a') {
            // For links, we remove script attributes like onmouseover
            // on both dvach & arhivach
            $node->removeAttribute('onmouseover');
            $node->removeAttribute('onmouseout');
            $node->removeAttribute('onclick');

            if ($this->isArhivachMode) {
                // <a href="http://arhivach.org/thread/25318/#356101"
                // style="color: rgb(120, 120, 120);">&gt;&gt;356101</a>
                $node->removeAttribute('style');
            }
        }

        if ($name === 'span') {
            $class = $node->getAttribute('class');
            if (!$class) {
                throw new InvalidMarkupException('span node must have a class set');
            }

            if ($this->isArhivachMode) {
                if (DOMUtil::hasClass($class, 'media-expand-button')) {
                    // Archivach youtube video preview, remove it
                    // <span href="#" class="media-expand-button">[Развернуть]</span>
                    return false;
                }
            }

            if (!array_key_exists($class, self::$allowedSpanClasses)) {
                throw new InvalidMarkupException("Class '$class' is not allowed for tag 'span'");
            }
        }

        if ($name === 'a') {
            $class = $node->getAttribute('class');

            if ($class && !array_key_exists($class, self::$allowedAClasses)) {
                throw new InvalidMarkupException("Class '$class' is not allowed for tag 'a'");
            }
        }

        // Validate attributes list
        $allowedAttrs = self::$allowedTags[$name];

        foreach ($node->attributes as $attrNode) {
            if (!in_array($attrNode->name, $allowedAttrs)) {
                $attrName = $attrNode->name;

                throw new InvalidMarkupException("Attribute '$attrName' is not allowed for tag '$name'");
            }
        }

        if (!$node->hasChildNodes()) {
            return true;
        }

        $this->transformChildren($node);

        return true;
    }
}
