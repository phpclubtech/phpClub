<?php

declare(strict_types=1);

namespace Tests\ThreadParser;

use PHPUnit\Framework\TestCase;
use phpClub\ThreadParser\InvalidMarkupException;
use phpClub\ThreadParser\MarkupConverter;
use phpClub\Util\DOMUtil;

class MarkupConverterTest extends TestCase
{
    /** @var MarkupConverter */
    private $markupConverter;

    public function setUp()
    {
        $this->markupConverter = new MarkupConverter;
    }

    public function testDetectsInvalidTags()
    {
        $body = DOMUtil::createNode('<img src="invalid tag">');

        $this->expectException(InvalidMarkupException::class);
        $this->markupConverter->transformBody($body);
    }

    public function testDetectsNestedInvalidTags()
    {
        $body = DOMUtil::createNode('<p><img src="invalid tag"></p>');

        $this->expectException(InvalidMarkupException::class);
        $this->markupConverter->transformBody($body);
    }

    public function testDetectsInvalidAttribute()
    {
        $body = DOMUtil::createNode('<p invalid-attribute>Text</p>');

        $this->expectException(InvalidMarkupException::class);
        $this->markupConverter->transformBody($body);
    }

    public function testDetectsInvalidSpanClass()
    {
        $body = DOMUtil::createNode('<span class="invalid-class">Text</span>');

        $this->expectException(InvalidMarkupException::class);
        $this->markupConverter->transformBody($body);
    }

    public function testAllowsValidTags()
    {
        $markup = '
            <p>...</p> 
            <br>

            <em>...</em> 
            <strong>...</strong> 
            <sup>...</sup>
            <sub>...</sub>

            <pre><code>...</code></pre>
            <span class="code_container"><span class="code_line">..</span><br>...</span>

            <a href="https://example.com" rel="nofollow">...</a>
            <a href="https://example.com" target="_blank">...</a>
            <a onmouseover="showPostPreview(event)" onmouseout="delPostPreview(event)" href="#315927" onclick="highlight(315927)">>>315927</a>
            <a href="#380394" class="post-reply-link" data-thread="377570" data-num="380394">>>380394</a>

            <span class="unkfunc">...</span>
            <span class="spoiler">...</span>

            <span class="s">...</span>
            <span class="o">...</span>
            <span class="u">...</span>

            <font color="#C12267"><em>...</em></font>
            <span class="pomyanem">...</span>
        ';

        $body = DOMUtil::createFragment($markup);
        $this->markupConverter->transformBody($body);
    }
}