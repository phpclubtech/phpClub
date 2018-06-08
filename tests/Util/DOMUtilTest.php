<?php

namespace Tests\Util;

use phpClub\Util\DOMUtil;
use PHPUnit\Framework\TestCase;

class DOMUtilTest extends TestCase
{
    public function testCanCreateElement()
    {
        $node = DOMUtil::createNode('<em>Hello</em>');
        $this->assertEquals('em', mb_strtolower($node->nodeName));
        $this->assertContains('Hello', $node->textContent);
    }

    public function testCanCreateTextNode()
    {
        $node = DOMUtil::createNode('Hello world');
        $this->assertInstanceOf(\DOMText::class, $node);
        $this->assertContains('Hello world', $node->wholeText);
    }

    public function testCanCreateComment()
    {
        $node = DOMUtil::createNode('<!-- example -->');
        $this->assertInstanceOf(\DOMComment::class, $node);
        $this->assertContains('example', $node->data);
    }

    public function testParsesUtf8Correctly()
    {
        $node = DOMUtil::createNode('<em>Привет 世界</em>');
        $this->assertContains('Привет', $node->textContent);
        $this->assertContains('世界', $node->textContent);
    }

    /* public function testParsesBodyTag()
    {
        $node = DOMUtil::createNode('<body>Hello</body>');
        $this->assertEquals('body', mb_strtolower($node->nodeName));
        $this->assertContains('Hello', $node->textContent);
    }

    public function testParsesHtmlTag()
    {
        $node = DOMUtil::createNode('<html><body>Hello</body></html>');
        $this->assertEquals('html', mb_strtolower($node->nodeName));
        $this->assertContains('Hello', $node->textContent);
    }
    */

    public function testCreateFragment()
    {
        $fragment = DOMUtil::createFragment('<em>1</em><strong>2</strong>');
        $this->assertEquals(2, $fragment->childNodes->length);

        $emNode = $fragment->childNodes->item(0);
        $this->assertEquals('em', mb_strtolower($emNode->nodeName));

        $strongNode = $fragment->childNodes->item(1);
        $this->assertEquals('strong', mb_strtolower($strongNode->nodeName));
    }

    public function testTransformerPreservesContentWhenNotChanged()
    {
        $sourceHtml = '<div><a href="#">Text</a><!-- comment --></div>';
        $source = DOMUtil::createNode($sourceHtml);
        $result = DOMUtil::transformDomTree($source, function ($node) {
            return $node;
        });

        $this->assertCount(1, $result);

        $html = DOMUtil::getOuterHtml($result[0]);
        $html = $this->removeSpaceBetweenTags($html);
        $this->assertEquals($sourceHtml, $html);
    }

    public function testTransformerCanRemoveNodes()
    {
        $source = DOMUtil::createNode('<div><a></a><b><s></s></b></div>');
        $result = DOMUtil::transformDomTree($source, function ($node) {
            if (mb_strtolower($node->nodeName) == 'b') {
                return null;
            }

            return $node;
        });

        $this->assertCount(1, $result);

        $html = DOMUtil::getOuterHtml($result[0]);
        $html = $this->removeSpaceBetweenTags($html);
        $this->assertEquals('<div><a></a></div>', $html);
    }

    public function testTransformerCanModifyAttributes()
    {
        $source = DOMUtil::createNode('<div><a id="id1"><b></b></a></div>');
        $result = DOMUtil::transformDomTree($source, function ($node) {
            if (mb_strtolower($node->nodeName) == 'a') {
                $node->setAttribute('id', 'id2');

                return $node;
            }

            return $node;
        });

        $this->assertCount(1, $result);

        $html = DOMUtil::getOuterHtml($result[0]);
        $html = $this->removeSpaceBetweenTags($html);
        $this->assertEquals('<div><a id="id2"><b></b></a></div>', $html);
    }

    public function testTransformerCanReplaceNode()
    {
        $source = DOMUtil::createNode('<div><a></a><b></b></div>');
        $result = DOMUtil::transformDomTree($source, function ($node) {
            if (mb_strtolower($node->nodeName) == 'a') {
                $newNode = $node->ownerDocument->createElement('p');

                return $newNode;
            }

            return $node;
        });

        $this->assertCount(1, $result);

        $html = DOMUtil::getOuterHtml($result[0]);
        $html = $this->removeSpaceBetweenTags($html);
        $this->assertEquals('<div><p></p><b></b></div>', $html);
    }

    public function testTransformerCanReplaceNodeWithSeveralNodes()
    {
        $source = DOMUtil::createNode('<div><a></a><b></b></div>');
        $result = DOMUtil::transformDomTree($source, function ($node) {
            if (mb_strtolower($node->nodeName) == 'a') {
                $newNode1 = $node->ownerDocument->createElement('p');
                $newNode2 = $node->ownerDocument->createElement('s');
                $frag = $node->ownerDocument->createDocumentFragment();
                $frag->appendChild($newNode1);
                $frag->appendChild($newNode2);

                return $frag;
            }

            return $node;
        });

        $this->assertCount(1, $result);

        $html = DOMUtil::getOuterHtml($result[0]);
        $html = $this->removeSpaceBetweenTags($html);
        $this->assertEquals('<div><p></p><s></s><b></b></div>', $html);
    }

    private function removeSpaceBetweenTags(string $html)
    {
        return preg_replace("/>\s+</", '><', $html);
    }

    public function testHasClassWorks()
    {
        $this->assertTrue(DOMUtil::hasClass('a b-c d', 'a'));
        $this->assertTrue(DOMUtil::hasClass('a b-c d', 'b-c'));

        // Negative cases
        $this->assertFalse(DOMUtil::hasClass('a b-c d', 'b'));
        $this->assertFalse(DOMUtil::hasClass('    ', 'a'));
    }
}
