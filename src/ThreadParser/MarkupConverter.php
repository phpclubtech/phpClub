<?php

declare(strict_types=1);

namespace phpClub\ThreadParser;

/**
 * Validates and transforms HTML markup inside post body. 
 */
class MarkupConverter
{
    /**
     * A whitelist of tags that can be used, and a whitelist of 
     * allowed attributes, e.g.:
     * 
     * <strong>text</strong>
     * <font color="...">...</font>
     */
    private static $allowedTags = [
        'p'         =>  [],
        'br'        =>  [],
        'strong'    =>  [],
        'em'        =>  [],
        'sub'       =>  [],
        'sup'       =>  [],
        'pre'       =>  [],
        'code'      =>  [],
        'font'      =>  ['color'],
        'span'      =>  ['class'],
        // There can also be attributes like onmouseover, onmouseout, onclick
        // but we strip them before validating
        'a'         =>  ['href', 'rel', 'target', 'class', 
            'data-thread', 'data-num']
    ];

    /**
     * Classes that can be used with spans, e.g.
     * <span class="spoiler">...</span>. 
     */
    private static $allowedSpanClasses = [
        'unkfunc'           =>  true, // a quote        
        's'                 =>  true, // strike-out
        'u'                 =>  true, // underline
        'o'                 =>  true, // overline
        'spoiler'           =>  true,
        'code_container'    =>  true,
        'code_line'         =>  true,
        'pomyanem'          =>  true, // message about a banner user from moderator
    ];

    /**
     * Classes that can be used on <a> tag.
     */
    private static $allowedAClasses = [
        'post-reply-link'   =>  true
    ];

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
     * @param \DOMDocumentFragment|\DOMElement|\DOMText $body 
     */
    public function transformBody(\DOMNode $body): void
    {
        $type = $body->nodeType;

        if ($type == XML_TEXT_NODE) {
            // Nothing to do
            return;
        }

        if ($type == XML_DOCUMENT_FRAG_NODE) {
            $this->transformIterable($body->childNodes);
            return;
        } 

        if ($type === XML_ELEMENT_NODE) {
            $this->transformElementRecursively($body);
            return;
        }

        throw new \InvalidArgumentException("Invalid DOM Node type; only text, element or document fragment node is allowed, given type='$type'");
    }

    /**
     * Processes all child nodes of a node, but doesn't do anything 
     * with the node itself.
     *
     * @param $nodeList iterable List of \DOMNodes 
     */
    public function transformIterable(iterable $nodeList): void
    {
        foreach ($nodeList as $node) {
            if ($node instanceof \DOMElement) {
                $this->transformElementRecursively($node);
            }
        }
    }    

    private function transformElementRecursively(\DOMElement $node): void
    {
        $name = mb_strtolower($node->nodeName);

        if (!array_key_exists($name, self::$allowedTags)) {
            throw new InvalidMarkupException("Tag '$name' is not allowed in markup");
        }

        if ($name == 'a') {
            // For links, we remove script attributes like onmouseover
            $node->removeAttribute('onmouseover');
            $node->removeAttribute('onmouseout');
            $node->removeAttribute('onclick');
        }

        // Validate attributes list
        $allowedAttrs = self::$allowedTags[$name];

        foreach ($node->attributes as $attrNode) {
            if (!in_array($attrNode->name, $allowedAttrs)) {
                $attrName = $attrNode->name;
                throw new InvalidMarkupException("Attribute '$attrName' is not allowed for tag '$name'");
            }
        }

        if ($name == 'span') {
            $class = $node->getAttribute('class');
            if (!$class) {
                throw new InvalidMarkupException("span node must have a class set");
            }

            if (!array_key_exists($class, self::$allowedSpanClasses)) {
                throw new InvalidMarkupException("Class '$class' is not allowed for tag 'span'");
            }
        }

        if ($name == 'a') {
            $class = $node->getAttribute('class');

            if ($class && !array_key_exists($class, self::$allowedAClasses)) {
                throw new InvalidMarkupException("Class '$class' is not allowed for tag 'a'");
            }
        }

        // Validate and transform children
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $this->transformElementRecursively($child);
            }
        }
    }
}