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
            $markdown = $this->parseCustomMarkdown($markdown);
            $html = $this->parser->text($markdown);
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
        $markdown = $this->parseStepsMarkdown($markdown);
        $markdown = $this->parseFeaturesMarkdown($markdown);
        $markdown = $this->parseCtaBannerMarkdown($markdown);
        $markdown = $this->parseCalloutBlockMarkdown($markdown);
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

    /**
     * Parse steps markdown syntax to HTML
     * Syntax: {steps-start} ... {step:1:Title} content {/step} ... {steps-end}
     *
     * @param string $markdown
     * @return string Markdown with Html elements for steps
     */
    public function parseStepsMarkdown(string $markdown): string
    {
        // Pattern pour capturer tout le bloc steps
        $pattern = '/\{steps-start\}(.*?)\{steps-end\}/s';

        return preg_replace_callback($pattern, function ($matches) {
            $stepsContent = $matches[1];

            // Pattern pour capturer chaque step individuel
            $stepPattern = '/\{step:(\d+):([^}]+)\}(.*?)\{\/step\}/s';

            $html = '<div class="steps-grid">' . "\n";

            preg_match_all($stepPattern, $stepsContent, $stepMatches, PREG_SET_ORDER);

            foreach ($stepMatches as $stepMatch) {
                $stepNumber = $stepMatch[1];
                $stepTitle = trim($stepMatch[2]);
                $stepContent = trim($stepMatch[3]);

                // Nettoyer et parser le contenu de l'étape
                $stepContent = $this->parseStepContent($stepContent);

                $html .= '<div class="step-card">' . "\n";
                $html .= '  <div class="step-header">' . "\n";
                $html .= '    <div class="step-number-compact">' . $stepNumber . '</div>' . "\n";
                $html .= '    <h4 class="step-title-compact">' . htmlspecialchars($stepTitle) . '</h4>' . "\n";
                $html .= '  </div>' . "\n";
                $html .= '  <div class="step-content-compact">' . "\n";
                $html .= $stepContent;
                $html .= '  </div>' . "\n";
                $html .= '</div>' . "\n";
            }

            $html .= '</div>' . "\n";

            return $html;
        }, $markdown);
    }

    /**
     * Parse step content (supports basic markdown inside steps)
     *
     * @param string $content
     * @return string
     */
    private function parseStepContent(string $content): string
    {
        // Nettoyer le contenu
        $content = trim($content);

        // Parser les éléments markdown de base
        $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
        $content = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $content);

        // Parser les listes
        $lines = explode("\n", $content);
        $inList = false;
        $result = '';

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match('/^[-*+]\s+(.+)/', $line, $matches)) {
                if (!$inList) {
                    $result .= "<ul>\n";
                    $inList = true;
                }
                $result .= "<li>" . $matches[1] . "</li>\n";
            } else {
                if ($inList) {
                    $result .= "</ul>\n";
                    $inList = false;
                }

                if (!empty($line)) {
                    $result .= "<p>" . $line . "</p>\n";
                }
            }
        }

        // Fermer la liste si elle est encore ouverte
        if ($inList) {
            $result .= "</ul>\n";
        }

        return $result;
    }

    /**
     * Parse features markdown syntax to HTML
     * Syntax: {features-start} ... {feature:full-fa-classes:Title:Description} ... {features-end}
     *
     * @param string $markdown
     * @return string Markdown with Html elements for features
     */
    public function parseFeaturesMarkdown(string $markdown): string
    {
        // Pattern pour capturer tout le bloc features
        $pattern = '/\{features-start\}(.*?)\{features-end\}/s';

        return preg_replace_callback($pattern, function ($matches) {
            $featuresContent = $matches[1];

            // Pattern pour capturer chaque feature individuelle
            // Format: {feature:full-fa-classes:Title:Description}
            $featurePattern = '/\{feature:([^:]+):([^:]+):([^}]+)\}/';

            $html = '<div class="features-grid">' . "\n";

            preg_match_all($featurePattern, $featuresContent, $featureMatches, PREG_SET_ORDER);

            foreach ($featureMatches as $featureMatch) {
                $featureClasses = trim($featureMatch[1]);
                $featureTitle = trim($featureMatch[2]);
                $featureDescription = trim($featureMatch[3]);

                $html .= '<div class="feature-card">' . "\n";
                $html .= '  <div class="feature-header">' . "\n";
                $html .= '    <i class="' . htmlspecialchars($featureClasses) . ' feature-icon"></i>' . "\n";
                $html .= '    <h4 class="feature-title">' . htmlspecialchars($featureTitle) . '</h4>' . "\n";
                $html .= '  </div>' . "\n";
                $html .= '  <p class="feature-description">' . htmlspecialchars($featureDescription) . '</p>' . "\n";
                $html .= '</div>' . "\n";
            }

            $html .= '</div>' . "\n";

            return $html;
        }, $markdown);
    }

    /**
     * Parse CTA banner markdown syntax to HTML (version améliorée)
     * Nouvelle syntaxe: {cta-banner-start} ... {cta-banner-end}
     * Avec sections: {title}, {description}, {button1}, {button2}
     *
     * @param string $markdown
     * @return string Markdown with Html elements for CTA banner
     */
    public function parseCtaBannerMarkdown(string $markdown): string
    {
        // Nouvelle syntaxe pour supporter le contenu multi-lignes
        $pattern = '/\{cta-banner-start\}(.*?)\{cta-banner-end\}/s';

        return preg_replace_callback($pattern, function ($matches) {
            $content = $matches[1];

            // Extraire chaque section
            $title = $this->extractCtaBannerSection($content, 'title');
            $description = $this->extractCtaBannerSection($content, 'description');
            $button1 = $this->extractCtaBannerSection($content, 'button1');
            $button2 = $this->extractCtaBannerSection($content, 'button2');

            if (!$title || !$description || !$button1 || !$button2) {
                // Fallback vers l'ancienne syntaxe si la nouvelle échoue
                return $this->parseCtaBannerMarkdownLegacy($matches[0]);
            }

            // Parser le markdown dans la description
            $descriptionHtml = $this->parse($description, false); // Parse markdown basique

            $html = '<div class="cta-banner">' . "\n";
            $html .= '  <div class="cta-banner-content">' . "\n";
            $html .= '    <h3 class="cta-banner-title">' . htmlspecialchars(trim($title)) . '</h3>' . "\n";
            $html .= '    <div class="cta-banner-description">' . $descriptionHtml . '</div>' . "\n";
            $html .= '    <div class="cta-banner-buttons">' . "\n";

            // Parser les boutons
            $button1Data = $this->parseCtaButton($button1);
            $button2Data = $this->parseCtaButton($button2);

            $html .= '      <a href="' . htmlspecialchars($button1Data['url']) . '" class="cta-banner-btn primary">' . "\n";
            $html .= '        ' . htmlspecialchars($button1Data['text']) . "\n";
            $html .= '      </a>' . "\n";
            $html .= '      <a href="' . htmlspecialchars($button2Data['url']) . '" class="cta-banner-btn secondary">' . "\n";
            $html .= '        <i class="fas fa-package"></i>' . "\n";
            $html .= '        ' . htmlspecialchars($button2Data['text']) . "\n";
            $html .= '      </a>' . "\n";
            $html .= '    </div>' . "\n";
            $html .= '  </div>' . "\n";
            $html .= '</div>' . "\n";

            return $html;
        }, $markdown);
    }

    /**
     * Extraire une section spécifique du contenu CTA banner
     */
    private function extractCtaBannerSection(string $content, string $sectionName): ?string
    {
        $pattern = '/\{' . preg_quote($sectionName) . '\}(.*?)(?=\{[a-z0-9]+\}|\z)/s';

        if (preg_match($pattern, $content, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Parser les données d'un bouton (texte|url)
     */
    private function parseCtaButton(string $buttonContent): array
    {
        $parts = explode('|', trim($buttonContent), 2);

        return [
            'text' => isset($parts[0]) ? trim($parts[0]) : '',
            'url' => isset($parts[1]) ? trim($parts[1]) : '#'
        ];
    }

    /**
     * Fallback vers l'ancienne syntaxe pour compatibilité
     */
    private function parseCtaBannerMarkdownLegacy(string $markdown): string
    {
        // Votre ancienne implémentation ici pour la compatibilité
        $pattern = '/\{cta-banner:([^:]+):([^:]+):([^:]+):([^:]+):([^:]+):([^}]+)\}/';

        return preg_replace_callback($pattern, function ($matches) {
            $title = trim($matches[1]);
            $description = trim($matches[2]);
            $button1Text = trim($matches[3]);
            $button1URL = trim($matches[4]);
            $button2Text = trim($matches[5]);
            $button2URL = trim($matches[6]);

            $html = '<div class="cta-banner">' . "\n";
            $html .= '  <div class="cta-banner-content">' . "\n";
            $html .= '    <h3 class="cta-banner-title">' . htmlspecialchars($title) . '</h3>' . "\n";
            $html .= '    <p class="cta-banner-description">' . htmlspecialchars($description) . '</p>' . "\n";
            $html .= '    <div class="cta-banner-buttons">' . "\n";
            $html .= '      <a href="' . htmlspecialchars($button1URL) . '" class="cta-banner-btn primary">' . "\n";
            $html .= '        ' . htmlspecialchars($button1Text) . "\n";
            $html .= '      </a>' . "\n";
            $html .= '      <a href="' . htmlspecialchars($button2URL) . '" class="cta-banner-btn secondary">' . "\n";
            $html .= '        <i class="fas fa-package"></i>' . "\n";
            $html .= '        ' . htmlspecialchars($button2Text) . "\n";
            $html .= '      </a>' . "\n";
            $html .= '    </div>' . "\n";
            $html .= '  </div>' . "\n";
            $html .= '</div>' . "\n";

            return $html;
        }, $markdown);
    }

    /**
     * Parse callout block markdown with support for markdown in description
     *
     * @param string $markdown
     * @return string
     */
    public function parseCalloutBlockMarkdown(string $markdown): string
    {
        // Pattern pour la syntaxe multi-lignes
        $pattern = '/\{callout-block-start\}\s*\{title\}\s*(.*?)\s*\{description\}\s*(.*?)\s*\{callout-block-end\}/s';

        return preg_replace_callback($pattern, function($matches) {
            $title = trim($matches[1]);
            $description = trim($matches[2]);

            // Convertir le markdown de la description en HTML
            $descriptionHtml = $this->parse($description);

            // Retourner le HTML du callout block avec les classes CSS existantes
            return $this->renderCalloutBlock($title, $descriptionHtml);
        }, $markdown);
    }

    /**
     * Render the callout block HTML using existing CSS classes
     *
     * @param string $title
     * @param string $descriptionHtml
     * @return string
     */
    private function renderCalloutBlock(string $title, string $descriptionHtml): string
    {
        return sprintf(
            '<div class="callout-block">
            <h3 class="callout-block-title">%s</h3>
            <div class="callout-block-description">%s</div>
        </div>',
            htmlspecialchars($title),
            $descriptionHtml
        );
    }
}