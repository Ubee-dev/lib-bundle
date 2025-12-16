<?php

namespace Khalil1608\LibBundle\Service;

use Khalil1608\LibBundle\Traits\VideoTrait;
use cebe\markdown\GithubMarkdown;
use cebe\markdown\Markdown;
use cebe\markdown\MarkdownExtra;
use Exception;
use ParsedownExtra;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Markdown Parser
 * Wrapper around third party markdown parser and filters
 * for processing the generated HTML.
 */
class MarkdownParser implements MarkdownParserInterface
{
    private ParsedownExtra $parser;
    private EntityManagerInterface $entityManager;
    private ?string $mediaClassName;
    private ?string $siteDomain;

    use VideoTrait;

    public function __construct(
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag
    ) {
        $this->parser = new ParsedownExtra();
        $this->entityManager = $entityManager;
        $this->mediaClassName = $parameterBag->get('mediaClassName');
        $this->siteDomain = $parameterBag->get('site.domain', null);
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
            $html = $this->addImageDimensions($html);
            $html = $this->addBSTableClass($html);
            $html = $this->addBSBlockquoteClass($html);
            $html = $this->addBSResponsiveTables($html);
            $html = $this->addLinkJsClass($html);
        } else {
            $html = $this->parser->text($this->parseSuperscriptMarkdown($markdown));
            $html = $this->removeMediaIdsFromImages($html);
            $html = $this->addImageDimensions($html);
        }

        return $html;
    }

    /**
     * Ajoute les dimensions (width/height) aux images en cherchant les médias correspondants
     *
     * @param string $html
     * @return string Html avec dimensions ajoutées
     */
    public function addImageDimensions(string $html): string
    {
        if (!$this->mediaClassName) {
            return $html;
        }

        $pattern = '/<img\s+src="([^"]+)"(\s+alt="([^"]*)")?([^>]*)\s*\/?>/i';

        return preg_replace_callback($pattern, function ($matches) {
            $src = $matches[1];
            $alt = $matches[3] ?? '';
            $otherAttributes = $matches[4] ?? '';

            // Extraire le nom du fichier de l'URL
            $filename = $this->extractFilenameFromUrl($src);

            if (!$filename) {
                return $matches[0]; // Retourner l'image originale si pas de filename
            }

            // Chercher le média correspondant
            $media = $this->findMediaByFilename($filename);

            if (!$media || !$media->isImage() || !$media->hasDimensions()) {
                return $matches[0]; // Retourner l'image originale si pas trouvé ou pas de dimensions
            }

            // Vérifier si width/height sont déjà présents
            if (preg_match('/\b(width|height)\s*=/', $otherAttributes)) {
                return $matches[0]; // Ne pas écraser les dimensions existantes
            }

            // Construire la nouvelle balise img avec dimensions
            $newImg = '<img src="' . $src . '"';

            if (!empty($alt)) {
                $newImg .= ' alt="' . $alt . '"';
            }

            $newImg .= ' width="' . $media->getWidth() . '"';
            $newImg .= ' height="' . $media->getHeight() . '"';

            if (!empty($otherAttributes)) {
                $newImg .= $otherAttributes;
            }

            $newImg .= ' />';

            return $newImg;
        }, $html);
    }

    /**
     * Extrait le nom du fichier à partir d'une URL
     *
     * @param string $url
     * @return string|null
     */
    private function extractFilenameFromUrl(string $url): ?string
    {
        // Gérer les URLs relatives et absolues
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';

        if (empty($path)) {
            return null;
        }

        // Extraire le nom du fichier
        $filename = basename($path);

        // Vérifier que c'est bien un fichier avec extension
        if (empty($filename) || !preg_match('/\.[a-zA-Z0-9]+$/', $filename)) {
            return null;
        }

        return $filename;
    }

    /**
     * Trouve un média par son nom de fichier
     *
     * @param string $filename
     * @return object|null
     */
    private function findMediaByFilename(string $filename): ?object
    {
        try {
            return $this->entityManager
                ->getRepository($this->mediaClassName)
                ->findOneBy(['filename' => $filename]);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas faire planter le parsing
            error_log("Error finding media by filename '$filename': " . $e->getMessage());
            return null;
        }
    }

    /**
     * @throws Exception
     */
    public function parseCustomMarkdown($markdown): string
    {
        $markdown = strip_tags($markdown,'<br>');
        $markdown = $this->parseStepsMarkdown($markdown);
        $markdown = $this->parseFeaturesMarkdown($markdown);
        $markdown = $this->parseEventsGridMarkdown($markdown);
        $markdown = $this->parseTimelineMarkdown($markdown);
        $markdown = $this->parseCtaBannerMarkdown($markdown);
        $markdown = $this->parseCtaBannerExtendedMarkdown($markdown);
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
            return '<img src="' . $url . '" alt="' . $alt . '"  />';
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
        return preg_replace_callback('/<a\s+href="([^"]*)"([^>]*)>/i', function($matches) {
            $href = $matches[1];
            $existingAttributes = $matches[2];

            // Skip if already has target attribute
            if (preg_match('/\btarget\s*=/', $existingAttributes)) {
                return $matches[0];
            }

            $isExternal = $this->isExternalLink($href);
            $shouldAddNofollow = $this->shouldAddNofollow($href);

            $attributes = [];

            // Ajouter la classe JS si elle n'existe pas déjà
            if (!preg_match('/\bclass\s*=/', $existingAttributes)) {
                $attributes[] = 'class="js-external-link-target"';
            }

            // Traitement des liens externes
            if ($isExternal) {
                $attributes[] = 'target="_blank"';
                $relValues = ['noopener', 'noreferrer'];

                if ($shouldAddNofollow) {
                    $relValues[] = 'nofollow';
                }

                $attributes[] = 'rel="' . implode(' ', $relValues) . '"';
            }

            // Construire le lien final
            $attributeString = empty($attributes) ? '' : ' ' . implode(' ', $attributes);
            return '<a href="' . $href . '"' . $existingAttributes . $attributeString . '>';
        }, $html);
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
                $html .= '    <span class="step-title-compact">' . htmlspecialchars($stepTitle) . '</span>' . "\n";
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
     * Parse features markdown syntax to HTML with full markdown support in descriptions
     * Syntax: {features-start} ... {feature:full-fa-classes:Title:Description} ... {features-end}
     *
     * @param string $markdown
     * @return string Markdown with Html elements for features (Lovable style)
     */
    public function parseFeaturesMarkdown(string $markdown): string
    {
        // Pattern pour capturer tout le bloc features
        $pattern = '/\{features-start\}(.*?)\{features-end\}/s';

        return preg_replace_callback($pattern, function ($matches) {
            $featuresContent = $matches[1];

            // Pattern pour capturer chaque feature individuelle
            // Format: {feature:full-fa-classes:Title:Description}
            // Le modificateur 's' permet de capturer les retours à la ligne dans la description
            $featurePattern = '/\{feature:([^:]+):([^:]+):([^}]+)\}/s';

            $html = '<div class="features-grid">' . "\n";

            preg_match_all($featurePattern, $featuresContent, $featureMatches, PREG_SET_ORDER);

            foreach ($featureMatches as $featureMatch) {
                $featureClasses = trim($featureMatch[1]);
                $featureTitle = trim($featureMatch[2]);
                $featureDescription = trim($featureMatch[3]);

                // Parser le markdown dans la description
                $featureDescriptionHtml = $this->parse($featureDescription, false);

                // Lovable style structure
                $html .= '<div class="feature-card">' . "\n";
                $html .= '  <div class="feature-content">' . "\n";
                $html .= '    <div class="feature-icon-wrapper">' . "\n";
                $html .= '      <i class="' . htmlspecialchars($featureClasses) . '"></i>' . "\n";
                $html .= '    </div>' . "\n";
                $html .= '    <div class="feature-text">' . "\n";
                $html .= '      <h4 class="feature-title">' . htmlspecialchars($featureTitle) . '</h4>' . "\n";
                $html .= '      <div class="feature-description">' . $featureDescriptionHtml . '</div>' . "\n";
                $html .= '    </div>' . "\n";
                $html .= '  </div>' . "\n";
                $html .= '</div>' . "\n";
            }

            $html .= '</div>' . "\n";

            return $html;
        }, $markdown);
    }

    /**
     * Parse events grid markdown syntax to HTML
     * Syntax:
     * {events-grid-start}
     * 1959|Thailand|6 nations
     * 1961|Myanmar|7 nations
     * {note}Optional note text here
     * {events-grid-end}
     *
     * @param string $markdown
     * @return string Markdown with Html elements for events grid
     */
    public function parseEventsGridMarkdown(string $markdown): string
    {
        $pattern = '/\{events-grid-start\}(.*?)\{events-grid-end\}/s';

        return preg_replace_callback($pattern, function ($matches) {
            $content = $matches[1];

            // Extract note if present
            $note = '';
            if (preg_match('/\{note\}(.*?)(?=\{events-grid-end\}|\z)/s', $content, $noteMatch)) {
                $note = trim($noteMatch[1]);
                $content = preg_replace('/\{note\}.*$/s', '', $content);
            }

            $html = '<div class="events-grid-wrapper">' . "\n";
            $html .= '  <div class="events-grid">' . "\n";

            // Parse each event line
            $lines = array_filter(array_map('trim', explode("\n", trim($content))));

            foreach ($lines as $line) {
                // Skip empty lines or lines that don't match the pattern
                if (empty($line) || strpos($line, '|') === false) {
                    continue;
                }

                $parts = explode('|', $line, 3);
                if (count($parts) >= 2) {
                    $year = trim($parts[0]);
                    $location = trim($parts[1]);
                    $info = isset($parts[2]) ? trim($parts[2]) : '';

                    $html .= '    <div class="event-card">' . "\n";
                    $html .= '      <div class="event-card-year">' . "\n";
                    $html .= '        <i class="far fa-calendar"></i>' . "\n";
                    $html .= '        <span>' . htmlspecialchars($year) . '</span>' . "\n";
                    $html .= '      </div>' . "\n";
                    $html .= '      <div class="event-card-location">' . "\n";
                    $html .= '        <i class="fas fa-map-marker-alt"></i>' . "\n";
                    $html .= '        <span>' . htmlspecialchars($location) . '</span>' . "\n";
                    $html .= '      </div>' . "\n";

                    if (!empty($info)) {
                        $html .= '      <div class="event-card-info">' . htmlspecialchars($info) . '</div>' . "\n";
                    }

                    $html .= '    </div>' . "\n";
                }
            }

            $html .= '  </div>' . "\n";

            // Add note if present
            if (!empty($note)) {
                $html .= '  <div class="events-grid-note">' . "\n";
                $html .= '    <p><span class="note-icon">📅</span> ' . htmlspecialchars($note) . '</p>' . "\n";
                $html .= '  </div>' . "\n";
            }

            $html .= '</div>' . "\n";

            return $html;
        }, $markdown);
    }

    /**
     * Parse timeline markdown syntax to HTML (chronological tree)
     * Syntax:
     * {timeline-start}
     * {title}Notre Histoire
     * 1977|Philippines, Indonesia, Brunei joined; renamed Southeast Asian Games Federation.
     * 2003|Timor-Leste joined.
     * 2021|31st SEA Games in Vietnam postponed to 2022 due to COVID-19.
     * {timeline-end}
     *
     * @param string $markdown
     * @return string Markdown with Html elements for timeline
     */
    public function parseTimelineMarkdown(string $markdown): string
    {
        $pattern = '/\{timeline-start\}(.*?)\{timeline-end\}/s';

        return preg_replace_callback($pattern, function ($matches) {
            $content = $matches[1];

            // Extract title if present
            $title = '';
            if (preg_match('/\{title\}(.+?)(?=\n|$)/s', $content, $titleMatch)) {
                $title = trim($titleMatch[1]);
                $content = preg_replace('/\{title\}.+?(?=\n|$)/s', '', $content);
            }

            $html = '<div class="timeline-wrapper">' . "\n";

            // Add title if present
            if (!empty($title)) {
                $html .= '  <h3 class="timeline-title">' . htmlspecialchars($title) . '</h3>' . "\n";
            }

            $html .= '  <div class="timeline">' . "\n";

            // Parse each timeline item
            $lines = array_filter(array_map('trim', explode("\n", trim($content))));

            foreach ($lines as $line) {
                // Skip empty lines or lines that don't match the pattern
                if (empty($line) || strpos($line, '|') === false) {
                    continue;
                }

                $parts = explode('|', $line, 2);
                if (count($parts) >= 2) {
                    $year = trim($parts[0]);
                    $description = trim($parts[1]);

                    $html .= '    <div class="timeline-item">' . "\n";
                    $html .= '      <div class="timeline-content">' . "\n";
                    $html .= '        <div class="timeline-card">' . "\n";
                    $html .= '          <span class="timeline-year">' . htmlspecialchars($year) . '</span>' . "\n";
                    $html .= '          <p class="timeline-description">' . htmlspecialchars($description) . '</p>' . "\n";
                    $html .= '        </div>' . "\n";
                    $html .= '      </div>' . "\n";
                    $html .= '    </div>' . "\n";
                }
            }

            $html .= '  </div>' . "\n";
            $html .= '</div>' . "\n";

            return $html;
        }, $markdown);
    }

    /**
     * Parse CTA banner markdown with new multiline syntax
     *
     * @param string $markdown
     * @return string
     */
    public function parseCtaBannerMarkdown(string $markdown): string
    {
        // Pattern pour capturer tout le contenu entre {cta-banner-start} et {cta-banner-end}
        $pattern = '/\{cta-banner-start\}(.*?)\{cta-banner-end\}/s';

        return preg_replace_callback($pattern, function ($matches) {
            $content = $matches[1];

            // Extraire chaque section
            $title = $this->extractCtaBannerSection($content, 'title');
            $description = $this->extractCtaBannerSection($content, 'description');
            $button1 = $this->extractCtaBannerSection($content, 'button1');
            $button2 = $this->extractCtaBannerSection($content, 'button2');

            if (!$title || !$description || !$button1) {
                // Fallback vers l'ancienne syntaxe si la nouvelle échoue
                return $this->parseCtaBannerMarkdownLegacy($matches[0]);
            }

            // Parser le markdown dans la description
            $descriptionHtml = $this->parse($description, false); // Parse markdown basique

            $html = '<div class="cta-banner">' . "\n";
            $html .= '  <div class="cta-banner-content">' . "\n";
            $html .= '    <h3 class="cta-banner-title">' . htmlspecialchars(trim($title)) . '</h3>' . "\n";
            $html .= '    <div class="cta-banner-description rich-content">' . $descriptionHtml . '</div>' . "\n";
            $html .= '    <div class="cta-banner-buttons">' . "\n";

            // Parser le bouton 1 (obligatoire)
            $button1Data = $this->parseCtaButton($button1);

            $html .= '      <a href="' . htmlspecialchars($button1Data['url']) . '" class="cta-banner-btn primary">' . "\n";
            $html .= '        ' . htmlspecialchars($button1Data['text']) . "\n";
            $html .= '      </a>' . "\n";

            // Parser le bouton 2 uniquement s'il existe
            if ($button2) {
                $button2Data = $this->parseCtaButton($button2);
                $html .= '      <a href="' . htmlspecialchars($button2Data['url']) . '" class="cta-banner-btn secondary">' . "\n";
                $html .= '        <i class="fas fa-package"></i>' . "\n";
                $html .= '        ' . htmlspecialchars($button2Data['text']) . "\n";
                $html .= '      </a>' . "\n";
            }

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
     * Parse CTA Banner Extended markdown with two-column layout and features
     * Syntax:
     * {cta-banner-extended-start}
     * {title}Your title here
     * {description}Your description here
     * {button1}Button Text|/url
     * {button2}Button Text|/url
     * {features}
     * fas fa-icon|Feature Title|Feature description
     * fas fa-icon|Feature Title|Feature description
     * {/features}
     * {cta-banner-extended-end}
     *
     * @param string $markdown
     * @return string
     */
    public function parseCtaBannerExtendedMarkdown(string $markdown): string
    {
        $pattern = '/\{cta-banner-extended-start\}(.*?)\{cta-banner-extended-end\}/s';

        return preg_replace_callback($pattern, function ($matches) {
            $content = $matches[1];

            // Extract sections
            $title = $this->extractCtaBannerSection($content, 'title');
            $description = $this->extractCtaBannerSection($content, 'description');
            $button1 = $this->extractCtaBannerSection($content, 'button1');
            $button2 = $this->extractCtaBannerSection($content, 'button2');

            // Extract features block
            $featuresContent = '';
            if (preg_match('/\{features\}(.*?)\{\/features\}/s', $content, $featuresMatch)) {
                $featuresContent = trim($featuresMatch[1]);
            }

            if (!$title || !$description) {
                return $matches[0]; // Return original if invalid
            }

            $html = '<div class="cta-banner-extended">' . "\n";
            $html .= '  <div class="cta-banner-extended-inner">' . "\n";
            $html .= '    <div class="cta-banner-extended-grid">' . "\n";

            // Left column - Content
            $html .= '      <div class="cta-banner-extended-content">' . "\n";
            $html .= '        <h3>' . htmlspecialchars(trim($title)) . '</h3>' . "\n";
            $html .= '        <p>' . htmlspecialchars(trim($description)) . '</p>' . "\n";

            // Buttons
            if ($button1 || $button2) {
                $html .= '        <div class="cta-banner-extended-buttons">' . "\n";

                if ($button1) {
                    $button1Data = $this->parseCtaButton($button1);
                    $html .= '          <a href="' . htmlspecialchars($button1Data['url']) . '" class="cta-banner-extended-btn primary">' . "\n";
                    $html .= '            ' . htmlspecialchars($button1Data['text']) . "\n";
                    $html .= '          </a>' . "\n";
                }

                if ($button2) {
                    $button2Data = $this->parseCtaButton($button2);
                    $html .= '          <a href="' . htmlspecialchars($button2Data['url']) . '" class="cta-banner-extended-btn secondary">' . "\n";
                    $html .= '            ' . htmlspecialchars($button2Data['text']) . "\n";
                    $html .= '          </a>' . "\n";
                }

                $html .= '        </div>' . "\n";
            }

            $html .= '      </div>' . "\n";

            // Right column - Features
            if ($featuresContent) {
                $html .= '      <div class="cta-banner-extended-features">' . "\n";

                $featureLines = array_filter(array_map('trim', explode("\n", $featuresContent)));
                foreach ($featureLines as $featureLine) {
                    $parts = explode('|', $featureLine, 3);
                    if (count($parts) >= 3) {
                        $icon = trim($parts[0]);
                        $featureTitle = trim($parts[1]);
                        $featureDesc = trim($parts[2]);

                        $html .= '        <div class="cta-banner-extended-feature">' . "\n";
                        $html .= '          <div class="cta-banner-extended-feature-icon">' . "\n";
                        $html .= '            <i class="' . htmlspecialchars($icon) . '"></i>' . "\n";
                        $html .= '          </div>' . "\n";
                        $html .= '          <div class="cta-banner-extended-feature-text">' . "\n";
                        $html .= '            <h4>' . htmlspecialchars($featureTitle) . '</h4>' . "\n";
                        $html .= '            <p>' . htmlspecialchars($featureDesc) . '</p>' . "\n";
                        $html .= '          </div>' . "\n";
                        $html .= '        </div>' . "\n";
                    }
                }

                $html .= '      </div>' . "\n";
            }

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

    /**
     * Determine if a link is external
     *
     * @param string $href
     * @return bool
     */
    private function isExternalLink(string $href): bool
    {
        // Liens relatifs et ancres = internes
        if (empty($href) || $href[0] === '/' || $href[0] === '#') {
            return false;
        }

        // Liens mailto, tel, etc. = pas externes au sens web
        if (preg_match('/^(mailto|tel|sms|ftp):/i', $href)) {
            return false;
        }

        // Si pas de domaine du site configuré, considérer tous les liens absolus comme externes
        if (!$this->siteDomain) {
            return preg_match('/^https?:\/\//i', $href);
        }

        // Parser l'URL pour extraire le domaine
        $parsedUrl = parse_url($href);
        if (!isset($parsedUrl['host'])) {
            return false; // URL relative ou malformée
        }

        $linkDomain = strtolower($parsedUrl['host']);
        $siteDomain = strtolower($this->siteDomain);

        // Supprimer "www." pour la comparaison
        $linkDomain = preg_replace('/^www\./', '', $linkDomain);
        $siteDomain = preg_replace('/^www\./', '', $siteDomain);

        return $linkDomain !== $siteDomain;
    }

// 5. Ajouter cette nouvelle fonction
    /**
     * Determine if a link should have nofollow attribute
     *
     * @param string $href
     * @return bool
     */
    private function shouldAddNofollow(string $href): bool
    {
        $nofollowDomains = [
            'youtube.com',
            'youtu.be',
            'facebook.com',
            'fb.com',
            'instagram.com',
            'twitter.com',
            'x.com',
            'linkedin.com',
            'tiktok.com',
            'pinterest.com',
            'reddit.com',
            'discord.com',
            'telegram.org',
            'whatsapp.com',
            'snapchat.com',
            'amazon.com',
            'amazon.fr',
            'ebay.com',
            'aliexpress.com',
            'booking.com',
            'airbnb.com',
            'spotify.com',
            'netflix.com',
            'twitch.tv'
        ];

        $parsedUrl = parse_url(strtolower($href));
        if (!isset($parsedUrl['host'])) {
            return false;
        }

        $domain = preg_replace('/^www\./', '', $parsedUrl['host']);

        foreach ($nofollowDomains as $nofollowDomain) {
            if ($domain === $nofollowDomain || str_ends_with($domain, '.' . $nofollowDomain)) {
                return true;
            }
        }

        return false;
    }
}