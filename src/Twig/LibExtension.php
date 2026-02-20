<?php

namespace UbeeDev\LibBundle\Twig;

use UbeeDev\LibBundle\Entity\DateTime;
use UbeeDev\LibBundle\Model\PhoneNumberInterface;
use UbeeDev\LibBundle\Service\UtmManager;
use UbeeDev\LibBundle\Traits\PhoneNumberTrait;
use UbeeDev\LibBundle\Traits\VideoTrait;
use Collator;
use Normalizer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class LibExtension extends AbstractExtension implements GlobalsInterface
{
    use VideoTrait;
    use PhoneNumberTrait;

    private static array $minify_exts = ['css', 'js'];
    private bool $minified;
    private array $assets;

    public function __construct(
        protected RequestStack $requestStack,
        private UtmManager $utmManager,
        protected ParameterBagInterface $parameterBag,
        protected string $currentEnv
    )
    {
        $this->assets = (file_exists('rev-manifest.json')) ? json_decode(file_get_contents('rev-manifest.json'), true) : [];
        $this->minified = true;
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('asset_rev', [$this, 'assetRev'], ['needs_environment' => true]),
            new TwigFilter('youtubeEmbedUrl', [$this, 'youtubeEmbedUrlFilter']),
            new TwigFilter('embedUrl', [$this, 'embedUrlFilter']),
            new TwigFilter('youtubeThumbnailUrl', [$this, 'youtubeThumbnailUrlFilter']),
            new TwigFilter('slugify_ua_ids', [$this, 'slugifyUAIdsFilter']),
            new TwigFilter('utmParams', [$this, 'utmParams'])
        ];
    }

    public function youtubeEmbedUrlFilter(string $url, bool $autoplay = false): string
    {
        return $this->getYoutubeEmbedUrl($url, [
            'autoplay' => $autoplay
        ]);
    }

    public function embedUrlFilter(string $url, bool $autoplay = false): ?string
    {
        if(!$url) {
            return null;
        }

        return $this->isYoutubeUrl($url)
            ? $this->getYoutubeEmbedUrl($url, ['autoplay' => $autoplay])
            : $this->getVimeoEmbedUrl($url, ['autoplay' => $autoplay, 'portrait' => 0, 'title' => 0, 'byline' => 0])
            ;
    }

    public function youtubeThumbnailUrlFilter(string $url, string $quality = 'medium'): string
    {
        return $this->getYoutubeThumbUrlFromYoutubeUrl($url, $quality);
    }

    public function getGlobals(): array
    {
        return [
            'has_cookie_consent' => ($this->requestStack->getCurrentRequest()) ? $this->requestStack->getCurrentRequest()->cookies->has('hasCookieConsent') : false
        ];
    }

    public function assetRev(Environment $env, string $asset): string
    {
        $pathinfo = pathinfo($asset);
        if (!isset($pathinfo['extension'])) {
            return $asset;
        }
        return ($this->minify($env, $pathinfo)) ?: ((isset($this->assets[$asset])) ? 'dist/' . $this->assets[$asset] : 'dist/' . $asset);
    }

    public function minify(Environment $env, array $pathinfo): string|false
    {
        $min = sprintf(
            "%s/%s.min.%s",
            $pathinfo['dirname'],
            $pathinfo['filename'],
            $pathinfo['extension']
        );

        return (in_array($pathinfo['extension'], self::$minify_exts) &&
            isset($this->assets[$min]) &&
            $this->minified &&
            !$env->isDebug())
            ? $this->assets[$min] : false;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('removeKeys', [$this, 'removeKeys']),
            new TwigFunction('parameter', [$this, 'getParameter']),
            new TwigFunction('getMockedJSTimestamp', [$this, 'getMockedJSTimestamp']),
            new TwigFunction('getCountries', [$this, 'getCountries']),
            new TwigFunction('formattedPhoneNumber', [$this, 'formattedPhoneNumber']),
            new TwigFunction('getEnvName', [$this, 'getEnvName'], ['needs_environment' => true]),
        ];
    }

    public function removeKeys(array $array, array $keys): array
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
            }
        }
        return $array;
    }

    public function getParameter(string $parameterName): mixed
    {
        return $this->parameterBag->get($parameterName);
    }

    public function slugifyUAIdsFilter(string $UAIds): string
    {
        return preg_replace('/[, ]/', '_', $UAIds);
    }

    public function getMockedJSTimestamp(): ?int
    {
        $fileSystem = new Filesystem();
        $mockTimeFilePath = $this->parameterBag->get('kernel.project_dir').'/tests/assets/mockTime'.getenv('TEST_TOKEN').'.txt';
        if($fileSystem->exists($mockTimeFilePath)) {
            return (new DateTime(file_get_contents($mockTimeFilePath)))->getTimestamp() * 1000;
        }

        return null;
    }

    public function getUTMParamsFromRequest(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        return $this->utmManager->getUtmParamsFromRequest($request);
    }

    public function utmParams(string $url): string
    {
        $request = $this->requestStack->getCurrentRequest();
        return $this->utmManager->utmParams($url, $request);
    }

    public function getCountries(): array
    {
        $json_countries_url = $this->parameterBag->get('kernel.project_dir').'/public/bundles/ubeedevlib/countries.json';
        $json_countries_content = normalizer_normalize(file_get_contents($json_countries_url),Normalizer::FORM_C );
        $json_countries =  json_decode( $json_countries_content, true);
        $collator = new Collator('fr_FR');
        $collator->asort($json_countries['national']);
        $collator->asort($json_countries['international']);
        return $json_countries;
    }

    public function formattedPhoneNumber(PhoneNumberInterface $entity): string
    {
        return $this->getFormattedPhoneNumber($entity->getCountryCallingCode(), $entity->getPhoneNumber());
    }

    public function getEnvName(): ?string
    {
        return match ($this->currentEnv) {
            'dev' => 'local',
            default => null,
        };
    }
}
