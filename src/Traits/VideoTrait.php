<?php

namespace Khalil1608\LibBundle\Traits;

use Exception;

trait VideoTrait
{
    /**
     * @throws Exception
     */
    public function extractYoutubeVideoIdFromUrl($url): string
    {
        if (!preg_match($this->getYoutubeRegex(), urldecode(trim($url)), $matches)) {
            throw new Exception("Failed to extract YouTube video id. YouTube URL is not valid.");
        }

        unset($matches[0]);
        return current(array_filter($matches));
    }

    public function isYoutubeUrl(string $url): bool
    {
        return (bool)preg_match($this->getYoutubeRegex(), urldecode($url));
    }

    public function isVimeoUrl(string $url): bool
    {
        return (bool)preg_match($this->getVimeoRegex(), urldecode($url));
    }

    /**
     * @return string
     */
    private function getYoutubeRegex(): string
    {
        return "/^(?:http(?:s)?:\/\/)(?:youtu.be\/([^\?\&]*)|(?:www\.)?youtube.com\/(?:live\/|embed\/|watch\?v=)([^\?\&]*))/";
    }

    private function getVimeoRegex(): string
    {
        return '/^https?:\/\/(?:www\.)?(?:player\.vimeo\.com\/video\/|vimeo\.com\/(?:event\/)?)?(\d{6,11})(?:\/embed)?(?:\?.*)?/';
    }

    /**
     * @throws Exception
     */
    public function getYoutubeEmbedUrl(string $url, array $options = []): string
    {
        if (!$this->isYoutubeUrl($url)) { return $url; }

        $parsedUrl = parse_url($url);
        $youtubeUrlOptions = [];

        if(array_key_exists('query', $parsedUrl)) {
            parse_str($parsedUrl['query'], $youtubeUrlOptions );
        }

        if(array_key_exists('v', $youtubeUrlOptions)) {
            unset($youtubeUrlOptions['v']);
        }

        $defaultOptions = ['rel' => 0, 'modestbranding' => 1];
        $mergedOptions = array_merge($youtubeUrlOptions, $defaultOptions, $options);
        ksort($mergedOptions);

        $embedUrl = 'https://www.youtube.com/embed/' . $this->extractYoutubeVideoIdFromUrl($url);
        return $embedUrl . '?' . http_build_query($mergedOptions);
    }

    /**
     * @param string $youtubeVideoId
     * @param string $quality "high"|"medium"|"default"
     * @return string
     */
    public function getYoutubeThumbUrl(string $youtubeVideoId, $quality): string
    {
        if ($quality == 'high') {
            return '//img.youtube.com/vi/' . $youtubeVideoId . '/hqdefault.jpg';
        } elseif ($quality == 'medium') {
            return '//img.youtube.com/vi/' . $youtubeVideoId . '/mqdefault.jpg';
        } elseif ($quality == 'default') {
            return '//img.youtube.com/vi/' . $youtubeVideoId . '/maxresdefault.jpg';
        } else {
            throw new \InvalidArgumentException("Failed to get YouTube thumb URL. Quality '" . $quality . "' not found.");
        }
    }

    /**
     * @throws Exception
     */
    public function getYoutubeThumbUrlFromYoutubeUrl($url, $quality): string
    {
        return $this->getYoutubeThumbUrl($this->extractYoutubeVideoIdFromUrl($url), $quality);
    }

    /**
     * @throws Exception
     */
    public function extractFacebookVideoIdFromEmbedCode($facebookEmbedHtml): string
    {
        if (!preg_match($this->getFacebookEmbedRegex(), urldecode($facebookEmbedHtml), $matches)) {
            throw new Exception("Failed to extract Facebook video id. HTML of embedded Facebook content is not valid.");
        }
        return $matches[1];
    }

    private function getFacebookEmbedRegex(): string
    {
        return "/(?:https?:\/\/)?(?:www\.|m\.)?(?:facebook\.com\/)plugins\/video\.php\?href=https\:\/\/www\.facebook\.com\/[^\/]*\/videos\/(\d*)/";
    }

    public function getFacebookThumbUrl(string $facebookVideoId): string
    {
        return 'https://graph.facebook.com/' . $facebookVideoId . '/picture';
    }

    /**
     * @throws Exception
     */
    public function getFacebookEmbedUrl(string $facebookEmbedHtml, array $options = []): string
    {
        if (!preg_match('/href=([^&]+)\/.*/', urldecode($facebookEmbedHtml), $matches)) {
            throw new Exception("Failed to get a Facebook embed URL. HTML of embedded Facebook content is not valid.");
        }
        return ($options) ?  $matches[1] . '&' . http_build_query($options) : $matches[1];
    }

    public function isFacebookEmbedUrl(string $facebookEmbedUrl): bool
    {
        return (bool) preg_match($this->getFacebookEmbedRegex(), urldecode($facebookEmbedUrl));
    }

    /**
     * @throws Exception
     */
    public function getVimeoEmbedUrl($url, $options = []): string
    {
        if($this->isVimeoLiveEventUrl($url)) {
            $embedUrl = 'https://vimeo.com/event/' . $this->extractVimeoVideoIdFromUrl($url);
        } else {
            $embedUrl = 'https://player.vimeo.com/video/' . $this->extractVimeoVideoIdFromUrl($url);
        }

        return count($options) > 0 ? $embedUrl . '?' . http_build_query($options) : $embedUrl.'?portrait=0&title=0&byline=0';
    }

    /**
     * @throws Exception
     */
    public function extractVimeoVideoIdFromUrl(string $url): string
    {
        if (preg_match($this->getVimeoRegex(), $url, $matches)) {
            return $matches[1] ?? $matches[2] ?? $matches[3];
        }

        throw new Exception("Failed to extract Vimeo video ID. Vimeo URL is not valid.");
    }

    public function isVimeoLiveEventUrl(string $url): bool
    {
        return str_contains($url, 'https://vimeo.com/event');
    }

    /**
     * @throws Exception
     */
    public function getLazyIframeHtml(
        string $videoUrl,
    ): string
    {

        if($this->isVimeoUrl($videoUrl)) {
            $videoId = $this->extractVimeoVideoIdFromUrl($videoUrl);
            $videoEmbedUrl = $this->getVimeoEmbedUrl($videoUrl, ['autoplay' => 1]);
            $thumbUrl = null;
            $provider = 'vimeo';
        } else {
            $videoId = $this->extractYoutubeVideoIdFromUrl($videoUrl);
            $videoEmbedUrl = $this->getYoutubeEmbedUrl($videoUrl, ['autoplay' => 1]);
            $thumbUrl = $this->getYoutubeThumbUrlFromYoutubeUrl($videoUrl, 'high');
            $provider = 'youtube';
        }

        return "<div class=\"ratio ratio-16x9\"><div class=\"js-lazyframe\" data-vendor=\"$provider\" data-video-id=\"$videoId\" data-src=\"$videoEmbedUrl\" data-thumbnail=\"$thumbUrl\"></div></div>";
    }
}
