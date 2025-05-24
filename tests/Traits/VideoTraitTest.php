<?php

namespace Khalil1608\LibBundle\Tests\Traits;

use Khalil1608\LibBundle\Tests\AbstractWebTestCase;
use Khalil1608\LibBundle\Traits\VideoTrait;
use Exception;
use InvalidArgumentException;

class VideoTraitTest extends AbstractWebTestCase
{
    use VideoTrait;
    private array $youtubeUrlValidFormats = [
        'https://www.youtube.com/embed/_h7hjWRRXek',
        'https://www.youtube.com/watch?v=_h7hjWRRXek',
        'http://www.youtube.com/watch?v=_h7hjWRRXek',
        'https://youtube.com/embed/_h7hjWRRXek',
        'https://youtu.be/_h7hjWRRXek',
        'https://youtube.com/live/_h7hjWRRXek',
    ];

    public function testIsYoutubeUrl(): void
    {
        $this->assertFalse($this->isYoutubeUrl('http://www.google.com/youtube'));
        $this->assertFalse($this->isYoutubeUrl('https://www.youtube.com'));
        $this->assertFalse($this->isYoutubeUrl('https://m.youtu.be/watch?v=z9A80I_a99A&feature=share'));
        $this->assertFalse($this->isYoutubeUrl('https://www.youtube.com/live_event_analytics?v=pEONnM1ZWzA&ar=1'));

        foreach ($this->youtubeUrlValidFormats as $url) {
            $this->assertTrue($this->isYoutubeUrl($url));
        }
    }

    /**
     * @throws Exception
     */
    public function testExtractYoutubeVideoIdFromUrl(): void
    {
        foreach ($this->youtubeUrlValidFormats as $url) {
            $urlWithQueryParameter = str_contains($url, '?') ? $url.'&test=1' : $url.'?test=1';
            $this->assertEquals('_h7hjWRRXek', $this->extractYoutubeVideoIdFromUrl($url));
            $this->assertEquals('_h7hjWRRXek', $this->extractYoutubeVideoIdFromUrl($urlWithQueryParameter));
        }
    }

    /**
     * @throws Exception
     */
    public function testGetYoutubeEmbedUrlWithNoUrlOption(): void
    {
        foreach ($this->youtubeUrlValidFormats as $url) {
            $this->assertEquals(
                'https://www.youtube.com/embed/_h7hjWRRXek?modestbranding=1&rel=0',
                $this->getYoutubeEmbedUrl($url)
            );
        }
    }

    /**
     * @throws Exception
     */
    public function testGetYoutubeEmbedUrlWithOnlyYoutubeUrl(): void
    {
        $this->assertEquals(
            'https://www.youtube.com/embed/z9A80I_a99A?modestbranding=1&rel=0',
            $this->getYoutubeEmbedUrl('https://www.youtube.com/watch?v=z9A80I_a99A')
        );
    }

    /**
     * @throws Exception
     */
    public function testGetYoutubeEmbedUrlWithYoutubeUrlAndOptions(): void
    {
        $this->assertEquals(
            'https://www.youtube.com/embed/z9A80I_a99A?autoplay=1&end=80&modestbranding=1&rel=0&some=params&start=60',
            $this->getYoutubeEmbedUrl(
                'https://www.youtube.com/watch?v=z9A80I_a99A?rel=0&start=110&modestbranding=0&some=params',
                ['autoplay' => 1, 'start' => 60, 'end' => 80]
            )
        );

        $this->assertEquals(
            'https://www.youtube.com/embed/z9A80I_a99A?autoplay=1&end=80&modestbranding=1&rel=0&some=params&start=60',
            $this->getYoutubeEmbedUrl(
                'https://www.youtube.com/embed/z9A80I_a99A?rel=0&start=110&modestbranding=0&some=params',
                ['autoplay' => 1, 'start' => 60, 'end' => 80]
            )
        );

        $this->assertEquals(
            'https://www.youtube.com/embed/z9A80I_a99A?autoplay=1&end=80&modestbranding=1&rel=0&some=params&start=60',
            $this->getYoutubeEmbedUrl(
                'http://www.youtube.com/watch?v=z9A80I_a99A&rel=0&start=110&modestbranding=0&some=params',
                ['autoplay' => 1, 'start' => 60, 'end' => 80]
            )
        );

        $this->assertEquals(
            'https://www.youtube.com/embed/z9A80I_a99A?autoplay=1&end=80&modestbranding=1&rel=0&some=params&start=60',
            $this->getYoutubeEmbedUrl(
                'https://youtube.com/embed/z9A80I_a99A?rel=0&start=110&modestbranding=0&some=params',
                ['autoplay' => 1, 'start' => 60, 'end' => 80]
            )
        );

        $this->assertEquals(
            'https://www.youtube.com/embed/z9A80I_a99A?autoplay=1&end=80&modestbranding=1&rel=0&start=60',
            $this->getYoutubeEmbedUrl(
                'https://youtu.be/z9A80I_a99A',
                ['autoplay' => 1, 'start' => 60, 'end' => 80]
            )
        );

        $this->assertEquals(
            'https://www.youtube.com/embed/z9A80I_a99A?autoplay=1&end=80&modestbranding=1&rel=0&some=params&start=60',
            $this->getYoutubeEmbedUrl(
                'https://youtube.com/live/z9A80I_a99A?rel=0&start=110&modestbranding=0&some=params',
                ['autoplay' => 1, 'start' => 60, 'end' => 80]
            )
        );
    }

    //Test : priority between url query, passed options and default options
    /**
     * @throws Exception
     */
    public function testGetYoutubeEmbedUrlWithBadUrls(): void
    {
        $this->assertEquals('https://www.google.com/youtube', $this->getYoutubeEmbedUrl('https://www.google.com/youtube'));
    }

    public function testGetYoutubeThumbUrl(): void
    {
        $this->assertEquals(
            '//img.youtube.com/vi/z9A80I_a99A/hqdefault.jpg',
            $this->getYoutubeThumbUrl('z9A80I_a99A', 'high')
        );
        $this->assertEquals(
            '//img.youtube.com/vi/z9A80I_a99A/mqdefault.jpg',
            $this->getYoutubeThumbUrl('z9A80I_a99A', 'medium')
        );
        $this->assertEquals(
            '//img.youtube.com/vi/z9A80I_a99A/maxresdefault.jpg',
            $this->getYoutubeThumbUrl('z9A80I_a99A', 'default')
        );

        try {
            $this->getYoutubeThumbUrl('z9A80I_a99A', 'quality_does_not_exist');
        } catch (InvalidArgumentException $e) {
            $this->assertEquals("Failed to get YouTube thumb URL. Quality 'quality_does_not_exist' not found.", $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function testGetYoutubeThumbUrlFromYoutubeUrl(): void
    {
        foreach ($this->youtubeUrlValidFormats as $url) {
            $this->assertEquals(
                '//img.youtube.com/vi/_h7hjWRRXek/hqdefault.jpg',
                $this->getYoutubeThumbUrlFromYoutubeUrl($url, 'high')
            );
            $this->assertEquals(
                '//img.youtube.com/vi/_h7hjWRRXek/mqdefault.jpg',
                $this->getYoutubeThumbUrlFromYoutubeUrl($url, 'medium')
            );
            $this->assertEquals(
                '//img.youtube.com/vi/_h7hjWRRXek/maxresdefault.jpg',
                $this->getYoutubeThumbUrlFromYoutubeUrl($url, 'default')
            );
        }
    }

    public function testExtractFacebookVideoIdFromEmbedCode(): void
    {
        $this->assertEquals('1360651047291380', $this->extractFacebookVideoIdFromEmbedCode('<iframe src="https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2F274322399257589%2Fvideos%2F1360651047291380%2F&width=400" width="400" height="400" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true" allowFullScreen="true"></iframe>'));
        try {
            $this->extractFacebookVideoIdFromEmbedCode('<iframe src="https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2F274322399257589%2Fvideos" width="400" height="400" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true" allowFullScreen="true"></iframe>');
        } catch (Exception $e) {
            $this->assertEquals("Failed to extract Facebook video id. HTML of embedded Facebook content is not valid.", $e->getMessage());
        }
    }

    public function testGetFacebookThumbUrl(): void
    {
        $this->assertEquals('https://graph.facebook.com/1360651047291380/picture', $this->getFacebookThumbUrl('1360651047291380'));
    }

    public function testGetFacebookEmbedUrl(): void
    {
        // TO CHECK WITH THE FRONT GUYS
        $this->assertEquals("https://www.facebook.com/274322399257589/videos/1360651047291380", $this->getFacebookEmbedUrl('<iframe src="https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2F274322399257589%2Fvideos%2F1360651047291380%2F&width=400" width="400" height="400" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true" allowFullScreen="true"></iframe>'));
        try {
            $this->getFacebookEmbedUrl('<iframe width="400" height="400" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true" allowFullScreen="true"></iframe>');
        } catch (Exception $e) {
            $this->assertEquals("Failed to get a Facebook embed URL. HTML of embedded Facebook content is not valid.", $e->getMessage());
        }
    }

    public function testIsFacebookEmbedUrl(): void
    {
        $this->assertFalse($this->isFacebookEmbedUrl('https://www.youtube.com'));
        $this->assertFalse($this->isFacebookEmbedUrl('https://www.facebook.com'));

        $this->assertTrue($this->isFacebookEmbedUrl('<iframe src="https://www.facebook.com/plugins/video.php?href=https%3A%2F%2Fwww.facebook.com%2F274322399257589%2Fvideos%2F1360651047291380%2F&width=400" width="400" height="400" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true" allowFullScreen="true"></iframe>'));
    }

    /**
     * @throws Exception
     */
    public function testGetVimeoEmbedUrl(): void
    {
        $this->assertEquals('https://player.vimeo.com/video/312495110?portrait=0&title=0&byline=0', $this->getVimeoEmbedUrl('https://vimeo.com/312495110'));
        $this->assertEquals('https://vimeo.com/event/2144974?portrait=0&title=0&byline=0', $this->getVimeoEmbedUrl('https://vimeo.com/event/2144974'));
    }

    public function testIsVimeoUrl(): void
    {
        $this->assertTrue($this->isVimeoUrl('https://player.vimeo.com/video/312495110?portrait=0&title=0&byline=0'));
        $this->assertTrue($this->isVimeoUrl('https://player.vimeo.com/video/312495110'));
        $this->assertTrue($this->isVimeoUrl('https://vimeo.com/event/2144974/embed?portrait=0&title=0&byline=0'));
        $this->assertTrue($this->isVimeoUrl('https://vimeo.com/event/2144974'));
        $this->assertTrue($this->isVimeoUrl('https://vimeo.com/312495110'));
        $this->assertTrue($this->isVimeoUrl('https://vimeo.com/312495110/121651651'));
        $this->assertTrue($this->isVimeoUrl('https://vimeo.com/312495110?portrait=0&title=0&byline=0'));
        $this->assertFalse($this->isVimeoUrl('https://vimeo.com/manage/312495110'));
        $this->assertFalse($this->isVimeoUrl('https://vimeo.com/video/312495110'));
        //not at all a vimeo URL
        $this->assertFalse($this->isVimeoUrl('https://video.com/312495110'));
    }

    /**
     * @throws Exception
     */
    public function testExtractVimeoVideoIdFromUrl(): void
    {
        $this->assertEquals('312495110', $this->extractVimeoVideoIdFromUrl('https://player.vimeo.com/video/312495110?portrait=0&title=0&byline=0'));
        $this->assertEquals('312495110', $this->extractVimeoVideoIdFromUrl('https://player.vimeo.com/video/312495110'));
        $this->assertEquals('312495110', $this->extractVimeoVideoIdFromUrl('https://vimeo.com/event/312495110/embed?portrait=0&title=0&byline=0'));
        $this->assertEquals('312495110', $this->extractVimeoVideoIdFromUrl('https://vimeo.com/event/312495110'));
        $this->assertEquals('312495110', $this->extractVimeoVideoIdFromUrl('https://vimeo.com/312495110'));
        $this->assertEquals('312495110', $this->extractVimeoVideoIdFromUrl('https://vimeo.com/312495110?portrait=0&title=0&byline=0'));
        $this->assertEquals('312495110', $this->extractVimeoVideoIdFromUrl('https://vimeo.com/312495110/1231651'));
    }

    public function testIsVimeoRecurringEventUrl(): void
    {
        $this->assertFalse($this->isVimeoLiveEventUrl('https://player.vimeo.com/video/312495110?portrait=0&title=0&byline=0'));
        $this->assertTrue($this->isVimeoLiveEventUrl('https://vimeo.com/event/2144974/embed?portrait=0&title=0&byline=0'));
        $this->assertTrue($this->isVimeoLiveEventUrl('https://vimeo.com/event/2144974'));
        // a vimeo video URL, not a live event
        $this->assertFalse($this->isVimeoLiveEventUrl('https://vimeo.com/312495110'));
        //not at all a vimeo URL
        $this->assertFalse($this->isVimeoLiveEventUrl('https://video.com/312495110'));
    }

    /**
     * @throws \Exception
     */
    public function testGetLazyIframeHtmlForYoutubeVideo(): void
    {
        $youtubeVideoUrl = "https://www.youtube.com/watch?v=firstVideo";
        $youtubeEmbedUrl = $this->getYoutubeEmbedUrl($youtubeVideoUrl, ['autoplay' => 1]);
        $youtubeThumbUrl = $this->getYoutubeThumbUrlFromYoutubeUrl($youtubeVideoUrl, 'high');
        $html = $this->getLazyIframeHtml("https://www.youtube.com/watch?v=firstVideo");

        $this->assertStringContainsString("<div class=\"ratio ratio-16x9\"><div class=\"js-lazyframe\" data-vendor=\"youtube\" data-video-id=\"firstVideo\" data-src=\"$youtubeEmbedUrl\" data-thumbnail=\"$youtubeThumbUrl\"></div></div>", $html);
    }

    /**
     * @throws \Exception
     */
    public function testGetLazyIframeHtmlForVimeoVideo(): void
    {
        $vimeoVideoUrl = "https://vimeo.com/12345678";
        $vimeoEmbedUrl = $this->getVimeoEmbedUrl($vimeoVideoUrl, ['autoplay' => 1]);
        $vimeoThumbUrl = null;
        $html = $this->getLazyIframeHtml("https://vimeo.com/12345678");

        $this->assertStringContainsString("<div class=\"ratio ratio-16x9\"><div class=\"js-lazyframe\" data-vendor=\"vimeo\" data-video-id=\"12345678\" data-src=\"$vimeoEmbedUrl\" data-thumbnail=\"$vimeoThumbUrl\"></div></div>", $html);
    }
}
