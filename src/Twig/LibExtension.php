<?php

namespace Khalil1608\LibBundle\Twig;

use Khalil1608\LibBundle\Entity\DateTime;
use Khalil1608\LibBundle\Model\PhoneNumberInterface;
use Khalil1608\LibBundle\Service\UtmManager;
use Khalil1608\LibBundle\Traits\PhoneNumberTrait;
use Khalil1608\LibBundle\Traits\VideoTrait;
use Collator;
use Normalizer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class LibExtension extends AbstractExtension implements GlobalsInterface
{
    use VideoTrait;
    use PhoneNumberTrait;

    /**
     * The file extensions to check if there is a minified version
     *
     * @var array
     */
    private static $minify_exts = ['css', 'js'];

    /**
     * Whether to search for minified rev'd versions of the assets
     *
     * @var bool
     */
    private $minified;

    /**
     * The array of assets rev, raw_asset => rev_asset
     *
     * @var array
     */
    private $assets;

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

    /**
     * Transform youtube url in youtube embed url and Add youtube options [autoplay, start, end]
     * rel=0 and modestbranding=1 are allready set
     *
     * @param string $url
     * @param bool $autoplay
     * @param int $start
     * @param int $end
     *
     * @return string
     */
    public function youtubeEmbedUrlFilter($url, $autoplay = false)
    {
        return $this->getYoutubeEmbedUrl($url, [
            'autoplay' => $autoplay
        ]);
    }

    /**
     * Transform youtube url in youtube embed url and Add youtube options [autoplay, start, end]
     * rel=0 and modestbranding=1 are allready set
     *
     * @param string $url
     * @param bool $autoplay
     * @return string
     * @throws \Exception
     */
    public function embedUrlFilter($url, $autoplay = false)
    {
        if(!$url) {
            return null;
        }

        return $this->isYoutubeUrl($url)
            ? $this->getYoutubeEmbedUrl($url, ['autoplay' => $autoplay])
            : $this->getVimeoEmbedUrl($url, ['autoplay' => $autoplay, 'portrait' => 0, 'title' => 0, 'byline' => 0])
            ;
    }

    public function youtubeThumbnailUrlFilter($url, $quality = 'medium')
    {
        return $this->getYoutubeThumbUrlFromYoutubeUrl($url, $quality);
    }

    public function getGlobals(): array
    {
        return [
            'has_cookie_consent' => ($this->requestStack->getCurrentRequest()) ? $this->requestStack->getCurrentRequest()->cookies->has('hasCookieConsent') : false
        ];
    }

    /**
     * Gets the rev'd asset,
     *
     * @param \Twig_Environment $env The twig environment
     * @param string $asset The asset string to rev
     *
     * @return string The rev'd asset if available, else the original asset
     */
    public function assetRev(\Twig_Environment $env, $asset)
    {
        $pathinfo = pathinfo($asset);
        if (!isset($pathinfo['extension'])) {
            return $asset;
        }
        return ($this->minify($env, $pathinfo)) ?: ((isset($this->assets[$asset])) ? 'dist/' . $this->assets[$asset] : 'dist/' . $asset);
    }

    /**
     * Gets the minified asset
     *
     * @param \Twig_Environment $env The twig environment
     * @param array $pathinfo The pathinfo for the asset
     *
     * @return bool|string The minified rev'd asset if available, else false
     */
    public function minify($env, $pathinfo)
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
        return array(
            new TwigFunction('removeKeys', [$this, 'removeKeys']),
            new TwigFunction('parameter', [$this, 'getParameter']),
            new TwigFunction('getMockedJSTimestamp', [$this, 'getMockedJSTimestamp']),
            new TwigFunction('getCountries', [$this, 'getCountries']),
            new TwigFunction('formattedPhoneNumber', [$this, 'formattedPhoneNumber']),
            new TwigFunction('getEnvName', [$this, 'getEnvName'], ['needs_environment' => true]),
        );
    }

    public function removeKeys(array $array, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
            }
        }
        return $array;
    }

    public function getParameter($parameterName)
    {
        return $this->parameterBag->get($parameterName);
    }

    public function slugifyUAIdsFilter($UAIds)
    {
        return preg_replace('/[, ]/', '_', $UAIds);
    }

    /**
     * @return integer|null
     * @throws \Exception
     */
    public function getMockedJSTimestamp()
    {
        $fileSystem = new Filesystem();
        $mockTimeFilePath = $this->parameterBag->get('kernel.project_dir').'/tests/assets/mockTime'.getenv('TEST_TOKEN').'.txt';
        if($fileSystem->exists($mockTimeFilePath)) {
            return (new DateTime(file_get_contents($mockTimeFilePath)))->getTimestamp() * 1000;
        }

        return null;

    }

    public function getUTMParamsFromRequest(){
        $request = $this->requestStack->getCurrentRequest();
        return $this->utmManager->getUtmParamsFromRequest($request);
    }

    public function utmParams($url){
        $request = $this->requestStack->getCurrentRequest();
        return $this->utmManager->utmParams($url, $request);
    }

    /**
     * @return array
     */
    public function getCountries()
    {
        $json_countries_url = $this->parameterBag->get('kernel.project_dir').'/public/bundles/khalil1608lib/countries.json';
        $json_countries_content = normalizer_normalize(file_get_contents($json_countries_url),Normalizer::FORM_C ); // for w3c validation
        $json_countries =  json_decode( $json_countries_content, true);
        $collator = new Collator('fr_FR');
        $collator->asort($json_countries['national']);
        $collator->asort($json_countries['international']);
        return $json_countries;
    }

    /**
     * @param PhoneNumberInterface $entity
     * @return string
     */
    public function formattedPhoneNumber(PhoneNumberInterface $entity)
    {
        return $this->getFormattedPhoneNumber($entity->getCountryCallingCode(), $entity->getPhoneNumber());

    }

    /**
     *  @return string|null
     */
    public function getEnvName() {
        switch ($this->currentEnv) {
            case 'dev': return 'local';
            default: return null;
        }
    }
}
