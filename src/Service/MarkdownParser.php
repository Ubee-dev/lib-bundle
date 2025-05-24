<?php

namespace Khalil1608\LibBundle\Service;

use Khalil1608\LibBundle\Traits\VideoTrait;
use cebe\markdown\GithubMarkdown;
use cebe\markdown\Markdown;
use cebe\markdown\MarkdownExtra;
use Exception;
use ParsedownExtra;

/**
 * Markdown Parser
 * Wrapper around third party markdown parser and filters
 * for processing the generated HTML.
 */
class MarkdownParser implements MarkdownParserInterface
{
    private ParsedownExtra $parser;

    use VideoTrait;

    public function __construct()
    {
        $this->parser = new ParsedownExtra();
    }

    /**
     * Parse markdown
     * If second argument is true then execute chain of pre- and post-processing steps:
     *   * Parse custom youtube syntax to valid Youtube markup
     *   * Add Bootstrap classes and elements to make markdown responsive
     * otherwise just parse the basic markdown syntax.
     *
     * @param string|null $markdown
     * @param bool $fullParsing
     * @return string|null Html
     * @throws Exception
     */
    public function parse(?string $markdown, bool $fullParsing = true): ?string
    {
        if(!$markdown) {
            return null;
        }

        if ($fullParsing) {
            $html = $this->parser->text($this->parseCustomMarkdown($markdown));
            $html = $this->removeMediaIdsFromImages($html);
            $html = $this->addBSTableClass($html);
            $html = $this->addBSBlockquoteClass($html);
            $html = $this->addBSResponsiveTables($html);
            $html = $this->addLinkJsClass($html);
        } else {

            $html = $this->parser->text($this->parseSuperscriptMarkdown($markdown));
            $html = $this->removeMediaIdsFromImages($html);
        }

        return $html;
    }

    /**
     * @throws Exception
     */
    public function parseCustomMarkdown($markdown): string
    {
        $markdown = strip_tags($markdown,'<br>');
        $markdown = $this->parseYoutubeMarkdown($markdown);
        $markdown = $this->parseVimeoMarkdown($markdown);
        $markdown = $this->parseIframeMarkdown($markdown);
        $markdown = $this->parseStrongMarkdown($markdown);
        $markdown = $this->parseVideoTime($markdown);
        $markdown = $this->parseButtonMarkdown($markdown);
        return $this->parseSuperscriptMarkdown($markdown);
    }

    public function parseStrongMarkdown(string $markdown): string
    {
        return preg_replace("/\{strong:([^}]+)\}/", "<strong class=\"spotlight\">$1</strong>", $markdown);
    }

    /**
     * @param string $markdown
     * @return string Markdown with Html elements for Youtube videos
     * @throws Exception
     */
    public function parseYoutubeMarkdown(string $markdown): string
    {
        return preg_replace_callback('/{youtube:(.*)}/i', function ($matches) {
            $youtubeEmbedUrl = $this->getYoutubeEmbedUrl($matches[1]);
            return "<div class=\"ratio ratio-16x9\">".
                "<iframe src=\"$youtubeEmbedUrl\" allowfullscreen></iframe>".
            "</div>";
        }, $markdown);
    }

    /**
     * @param string $markdown
     * @return string Markdown with Html elements for Youtube videos
     * @throws Exception
     */
    public function parseVimeoMarkdown(string $markdown): string
    {
        return preg_replace_callback('/{vimeo:(.*)}/i', function ($matches) {
            $vimeoEmbedUrl = $this->getVimeoEmbedUrl($matches[1]);
            return "<div class=\"ratio ratio-16x9\">".
                "<iframe src=\"$vimeoEmbedUrl\" allowfullscreen></iframe>".
                "</div>";
        }, $markdown);
    }

    /**
     * @param string $markdown
     * @return string Markdown with Html elements for iframes
     */
    public function parseIframeMarkdown(string $markdown): string
    {
        $output = preg_replace("/\{iframe:([^,]*),height.(\d+)(px)?\}/", "<div class=\"js-iframe-with-loader-container iframe-with-loader-container\">\n<iframe src=\"$1\" class=\"js-iframe-with-loader\" height=\"$2\"></iframe>\n</div>", $markdown);
        return $output;
    }

    /**
     * @param string $markdown
     * @return string Markdown with Html elements for buttons
     */
    public function parseButtonMarkdown(string $markdown): string
    {
        return preg_replace("/\{\s?\[([^\]]*)\]\(([^\)]*)\)\s?\}/", "<div class=\"btn-container\">\n<a href=\"$2\" class=\"btn btn_dark js-external-link-target tk-markdown__cta\">$1</a>\n</div>", $markdown);
    }

    public function parseVideoTime(string $markdown): string
    {
        return preg_replace_callback("/{videoTime:((\d{1,2}:)?\d{1,2}:\d{2})}/i", function($matches) {
            return $this->videoTimeHTML($matches[1]);
        }, $markdown);
    }

    private function parseSuperscriptMarkdown(string $markdown): string
    {
        return preg_replace("/\^([\w|\s]+)\^/", "<sup>$1</sup>", $markdown);
    }

    private function videoTimeHTML(string $rawTime): string
    {
        $formattedTime = strlen($rawTime) > 5 ? ltrim($rawTime, '0') : $rawTime;
        $timeArray = array_reverse(explode(':', $rawTime));
        $seconds = 0;
        foreach ($timeArray as $index=>$time) {
            $seconds = $seconds + ((int)$time * 60 ** $index);
        }

        return "<a href=\"#\" data-video-time=\"$seconds\" class=\"js-video-time info__video-time\">$formattedTime</a>";
    }

    /**
     * Remove ids if exists from all <img/> elements
     *
     * @param string $html
     * @return string Html
     */
    public function removeMediaIdsFromImages(string $html): string
    {
        $pattern = '/<img\s+src="([^"]+)"(?:\s+alt="([^"]*)")?\s*\/?>/i';
        return preg_replace_callback($pattern, function ($matches) {
            $url = $matches[1];
            $alt = $matches[2] ?? '';

            // Supprime l'ID s'il existe
            $url = preg_replace('/\|\d+$/', '', $url);

            // Reconstitue la balise <img> avec l'URL modifiée et l'attribut alt
            return '<img src="' . $url . '" alt="' . $alt . '" />';
        }, $html);
    }

    /**
     * Add class attribute on all <table> elements
     *
     * @param string $html
     * @param string $class CSS class string
     * @return string Html
     */
    public function addBSTableClass(string $html, string $class = 'table table-bordered'): string
    {
        return preg_replace('/<table( class="[^"]*" )?/', '<table class="' . $class . '"', $html);
    }

    /**
     * Add class attribute on all <blockquote> elements
     *
     * @param string $html
     * @param string $class CSS class string
     * @return string Html
     */
    public function addBSBlockquoteClass(string $html, string $class = 'blockquote'): string
    {
        return preg_replace('/<blockquote( class="[^"]*" )?/', '<blockquote class="' . $class . '"', $html);
    }

    /**
     * Add Html elements and classes to make all tables responsive Bootstrap tables
     *
     * @param string $html
     * @return string Html
     */
    public function addBSResponsiveTables(string $html): string
    {
        $html = preg_replace('/<table[^>]*>/', "<div class=\"table-responsive\">\n$0", $html);
        return preg_replace('/<\/table>/', "$0\n</div>\n", $html);
    }

    /**
     * Add js class on link
     *
     * @param string $html
     * @return string Html
     */
    public function addLinkJsClass(string $html): string
    {
        return preg_replace('/<a (href="[^"]*")>/', '<a $1 class="js-external-link-target">', $html);
    }

    public function getMediaIds(string $markdown): array
    {
        $pattern = '/!\[.*\]\((?:http[^|]*\|(\d+)|[^)]*\|(\d+))\)/';
        $matches = [];

        preg_match_all($pattern, $markdown, $matches);

        $ids = array_filter(array_merge($matches[1], $matches[2]), static function ($id) {
            return !empty($id);
        });

        return array_map('intval', $ids);
    }
}
