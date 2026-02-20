<?php

namespace UbeeDev\LibBundle\Tests\Service;

use UbeeDev\LibBundle\Service\MarkdownParser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;

class MarkdownParserTest extends AbstractWebTestCase
{
    const string MARKDOWN_SAMPLE = "# Heading 1
## Heading 2 {#heading-2}
### Heading 3

* item 1
* item 2
* item 3

[Link text](http://www.example.com)

![](http://www.example.com/image.jpg)

![Alt image text with id](http://www.example.com/image2.jpg|123)

![](http://www.example.com/image3.jpg|456)

Paragraph text with **bold** and *italic* words.

> This is a quote.
> It can span multiple lines!

| Column 1 | Column 2 | Column 3 |
| -------- | -------- | -------- |
| John     | Doe      | Male     |
| Mary     | Smith    | Female   |

Mes videos :

{youtube:https://www.youtube.com/watch?v=firstVideo}

{youtube:https://www.youtube.com/watch?v=secondVideo}

Sommaire: 

{videoTime:1:00:00}
{videoTime:1:30:00}
{videoTime:0:45}
{videoTime:5:40}
{videoTime:25:10}
{videoTime:00:10}

Mon iframe :

{iframe:https://www.ycbm.com/UbeeDev/rdv-lca?p=2&amp;q=1,height:450}

{[Mon Bouton](https://www.google.fr)}

Mon 1^er^ post

J'adore DBZ 
*[DBZ]: Dragon Ball Z
";

    const string MARKDOWN_PARSED = <<<EOT
<h1>Heading 1</h1>
<h2 id="heading-2">Heading 2</h2>
<h3>Heading 3</h3>
<ul>
<li>item 1</li>
<li>item 2</li>
<li>item 3</li>
</ul>
<p><a class="js-external-link-target" href="http://www.example.com">Link text</a></p>
<p><img src="http://www.example.com/image.jpg" alt="" /></p>
<p><img src="http://www.example.com/image2.jpg" alt="Alt image text with id" /></p>
<p>Paragraph text with <strong>bold</strong> and <em>italic</em> words.</p>
<blockquote class="blockquote">
<p>This is a quote.
It can span multiple lines!</p>
</blockquote>
<div class="table-responsive">
<table class="table table-bordered">
<thead>
<tr>
<th>Column 1</th>
<th>Column 2</th>
<th>Column 3</th>
</tr>
</thead>
<tbody>
<tr>
<td>John</td>
<td>Doe</td>
<td>Male</td>
</tr>
<tr>
<td>Mary</td>
<td>Smith</td>
<td>Female</td>
</tr>
</tbody>
</table>
</div>
<p>Ma video : </p>
<div class="ratio ratio-16x9">
<iframe src="//www.youtube.com/embed/cC7QRT9refU?rel=0" allowfullscreen></iframe>
</div>
<p>Mon iframe : </p>
<div class="js-iframe-with-loader-container iframe-with-loader-container">
<iframe src="https://www.ycbm.com/UbeeDev/rdv-lca?p=2&amp;q=1" class="js-iframe-with-loader" height="500px"></iframe>
</div>
<div class="btn-container">
<a href="https://www.google.fr" class="btn btn_dark js-external-link-target tk-markdown__cta">Mon Bouton</a>
</div>
<p>Mon 1<sup>er</sup></p>
<p>J'adore <abbr title="Dragon Ball Z">DBZ</abbr></p>
EOT;

    /**
     * @var MarkdownParser
     */
    private $parser;

    protected function setUp(): void
    {
       parent::setUp();
       $this->parser = new MarkdownParser(
           entityManager: $this->entityManager,
           parameterBag: $this->container->getParameterBag()
       );
    }

    public function testParseBasic(): void
    {
        $html = $this->parser->parse(self::MARKDOWN_SAMPLE, false);
        $this->assertStringContainsString('<h1>Heading 1</h1>', $html);
        $this->assertStringContainsString('<h2 id="heading-2">Heading 2</h2>', $html);
        $this->assertStringContainsString('<h3>Heading 3</h3>', $html);
        $this->assertStringContainsString('<li>item 1</li>', $html);
        $this->assertStringContainsString('<li>item 2</li>', $html);
        $this->assertStringContainsString('<li>item 3</li>', $html);
        $this->assertStringContainsString('<a href="http://www.example.com">Link text</a>', $html);
        $this->assertStringContainsString('<img src="http://www.example.com/image.jpg" alt="" />', $html);
        $this->assertStringContainsString('<img src="http://www.example.com/image2.jpg" alt="Alt image text with id" />', $html);
        $this->assertStringContainsString('<img src="http://www.example.com/image3.jpg" alt="" />', $html);
        $this->assertStringContainsString('<p>Paragraph text with <strong>bold</strong> and <em>italic</em> words.</p>', $html);
        $this->assertMatchesRegularExpression('/<blockquote>\s*(<p>)?This is a quote.\s*It can span multiple lines!(<\/p>)?\s*<\/blockquote>/', $html);
        $this->assertStringContainsString("<table>\n<thead>\n<tr>\n<th>Column 1</th>\n<th>Column 2</th>\n<th>Column 3</th>\n</tr>\n</thead>\n<tbody>\n<tr>\n<td>John</td>\n<td>Doe</td>\n<td>Male</td>\n</tr>\n<tr>\n<td>Mary</td>\n<td>Smith</td>\n<td>Female</td>\n</tr>\n</tbody>\n</table>", $html);
        $this->assertStringContainsString('<p>Mon 1<sup>er</sup> post</p>', $html);
        $this->assertStringContainsString('<p>J\'adore <abbr title="Dragon Ball Z">DBZ</abbr> </p>', $html);
    }

    public function testParseFull(): void
    {
        $html = $this->parser->parse(self::MARKDOWN_SAMPLE, true);
        $this->assertStringContainsString('<h1>Heading 1</h1>', $html);
        $this->assertStringContainsString('<h2 id="heading-2">Heading 2</h2>', $html);
        $this->assertStringContainsString('<h3>Heading 3</h3>', $html);
        $this->assertStringContainsString('<li>item 1</li>', $html);
        $this->assertStringContainsString('<li>item 2</li>', $html);
        $this->assertStringContainsString('<li>item 3</li>', $html);
        $this->assertStringContainsString('<a href="http://www.example.com" class="js-external-link-target" target="_blank" rel="noopener noreferrer">Link text</a>', $html);

        $this->assertStringContainsString("<div class=\"ratio ratio-16x9\"><iframe src=\"https://www.youtube.com/embed/firstVideo?modestbranding=1&amp;rel=0\" allowfullscreen></iframe></div>", $html);
        $this->assertStringContainsString("<div class=\"ratio ratio-16x9\"><iframe src=\"https://www.youtube.com/embed/secondVideo?modestbranding=1&amp;rel=0\" allowfullscreen></iframe></div>", $html);

        $this->assertStringContainsString("<div class=\"js-iframe-with-loader-container iframe-with-loader-container\">\n<iframe src=\"https://www.ycbm.com/UbeeDev/rdv-lca?p=2&amp;q=1\" class=\"js-iframe-with-loader\" height=\"450\"></iframe>\n</div>", $html);

        $this->assertStringContainsString("<div class=\"btn-container\">\n<a href=\"https://www.google.fr\" class=\"btn btn_dark js-external-link-target tk-markdown__cta\" target=\"_blank\" rel=\"noopener noreferrer\">Mon Bouton</a>\n</div>", $html);

        $this->assertStringContainsString("<a href=\"#\" data-video-time=\"3600\" class=\"js-video-time info__video-time\">1:00:00</a>", $html);
        $this->assertStringContainsString("<a href=\"#\" data-video-time=\"5400\" class=\"js-video-time info__video-time\">1:30:00</a>", $html);

        $this->assertStringContainsString("<a href=\"#\" data-video-time=\"45\" class=\"js-video-time info__video-time\">0:45</a>", $html);

        $this->assertStringContainsString("<a href=\"#\" data-video-time=\"340\" class=\"js-video-time info__video-time\">5:40</a>", $html);
        $this->assertStringContainsString("<a href=\"#\" data-video-time=\"1510\" class=\"js-video-time info__video-time\">25:10</a>", $html);
        $this->assertStringContainsString("<a href=\"#\" data-video-time=\"10\" class=\"js-video-time info__video-time\">00:10</a>", $html);

        $this->assertStringContainsString('<img src="http://www.example.com/image.jpg" alt="" />', $html);
        $this->assertStringContainsString('<img src="http://www.example.com/image2.jpg" alt="Alt image text with id" />', $html);
        $this->assertStringContainsString('<img src="http://www.example.com/image3.jpg" alt="" />', $html);
        $this->assertStringContainsString('<p>Paragraph text with <strong>bold</strong> and <em>italic</em> words.</p>', $html);
        $this->assertMatchesRegularExpression('/<blockquote class="blockquote">\s*(<p>)?This is a quote.\s*It can span multiple lines!(<\/p>)?\s*<\/blockquote>/', $html);
        $this->assertMatchesRegularExpression('/<div class="table-responsive">\s*<table class="table table-bordered">/', $html);
        $this->assertStringContainsString('<p>Mon 1<sup>er</sup> post</p>', $html);
        $this->assertStringContainsString('<p>J\'adore <abbr title="Dragon Ball Z">DBZ</abbr> </p>', $html);
    }

    public function testParseStrongMarkdown(): void
    {
        $markdown = <<<EOT
This is a sample with some {strong:2} strong elements like {strong:10%}.
EOT;
        $this->assertEquals('This is a sample with some <strong class="spotlight">2</strong> strong elements like <strong class="spotlight">10%</strong>.', $this->parser->parseStrongMarkdown($markdown));
    }

    public function testGetMediaIds(): void
    {
        $this->assertEquals(
            [123, 456],
            $this->parser->getMediaIds(self::MARKDOWN_SAMPLE)
        );
    }
}
