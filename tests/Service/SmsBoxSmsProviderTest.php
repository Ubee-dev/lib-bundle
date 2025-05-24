<?php


namespace Khalil1608\LibBundle\Tests\Service;


use Khalil1608\LibBundle\Service\SmsBoxSmsProvider;
use Khalil1608\LibBundle\Tests\AbstractWebTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;

class SmsBoxSmsProviderTest extends AbstractWebTestCase
{
    /** @var SmsBoxSmsProvider */
    private $smsBoxSmsProvider;

    /** @var MockObject|Client */
    private $guzzleClientMock;

    /** @var array */
    private $recipients;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guzzleClientMock = $this->getMockedClass(Client::class);
        $this->recipients = [[
            'countryCallingCode' => '33',
            'phoneNumber' => '0611111111',
            'firstName' => 'Goku',
            'lastName' => 'San',
        ], [
            'countryCallingCode' => '33',
            'phoneNumber' => '0622222222',
            'firstName' => 'Gohan',
            'lastName' => 'Videl',
        ], [
            'countryCallingCode' => '33',
            'phoneNumber' => '0633333333',
            'firstName' => 'Vege',
            'lastName' => 'Ta'
        ]];
    }

    public function testSendingNonCommercialSmsWithoutUsingSandboxSuccessful()
    {
        $smsBoxSmsProvider = new SmsBoxSmsProvider('someapikey', 'http://smsbox/api.php', false, $this->guzzleClientMock);

        $this->guzzleClientMock->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('http://smsbox/api.php'),
                $this->equalTo([
                    'headers' => ['Authorization' => 'App someapikey'],
                    'query' => [],
                    'form_params' => [
                        'dest' => '33611111111,33622222222,33633333333',
                        'msg' => 'some sweet non commercial text',
                        'coding' => 'default',
                        'mode' => 'Expert',
                        'charset' => 'utf-8',
                        'origine' => 'Obj.CRPE',
                        'strategy' => 2,
                        'enable_short_url' => 'no',
                        'short_url_ssl' => 'yes'
                    ]
                ])
            )
            ->willReturn(new Response(200,[],'OK'));

        $smsBoxSmsProvider->send([
            'to' => $this->recipients,
            'from' => 'objectif-crpe',
            'message' => 'some sweet non commercial text',
            'isCommercial' => false,
            'shortenUrls' => false
        ]);
    }

    public function testSendingCommercialSmsUsingSandboxSuccessful()
    {
        $smsBoxSmsProvider = new SmsBoxSmsProvider('someapikey', 'http://smsbox/api.php', true, $this->guzzleClientMock);

        $this->guzzleClientMock->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('http://smsbox/api.php'),
                $this->equalTo([
                    'headers' => ['Authorization' => 'App someapikey'],
                    'query' => [],
                    'form_params' => [
                        'dest' => '9990611111111,9990622222222,9990633333333',
                        'msg' => 'some sweet non commercial text STOP SMS 36111',
                        'coding' => 'default',
                        'mode' => 'Expert',
                        'charset' => 'utf-8',
                        'origine' => 'Procompta',
                        'strategy' => 4,
                        'enable_short_url' => 'yes',
                        'short_url_ssl' => 'yes'
                    ]
                ])
            )
            ->willReturn(new Response(200, [], 'OK'));

        $smsBoxSmsProvider->send([
            'to' => $this->recipients,
            'from' => 'pro-compta',
            'message' => 'some sweet non commercial text',
            'isCommercial' => true,
            'shortenUrls' => true
        ]);
    }

    public function testSmsBoxErrorCodesTriggerExceptions()
    {
        $smsBoxSmsProvider = new SmsBoxSmsProvider('someapikey', 'http://smsbox/api.php', false, $this->guzzleClientMock);

        $this->guzzleClientMock->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('http://smsbox/api.php'),
                $this->equalTo([
                    'headers' => ['Authorization' => 'App someapikey'],
                    'query' => [],
                    'form_params' => [
                        'dest' => '33611111111,33622222222,33633333333',
                        'msg' => 'some sweet non commercial text STOP SMS 36111',
                        'coding' => 'default',
                        'mode' => 'Expert',
                        'charset' => 'utf-8',
                        'origine' => 'Procompta',
                        'strategy' => 4,
                        'enable_short_url' => 'yes',
                        'short_url_ssl' => 'yes'
                    ]
                ])
            )
            ->willReturn(new Response(200, [], 'ERROR 01 blablabla'));
        try {
            $smsBoxSmsProvider->send([
                'to' => $this->recipients,
                'from' => 'pro-compta',
                'message' => 'some sweet non commercial text',
                'isCommercial' => true,
                'shortenUrls' => true
            ]);
            $this->fail('An SmsBox error code should trigger an exception');
        } catch (\Exception $e) {
            $this->assertEquals('SmsBox error code : ERROR 01 blablabla', $e->getMessage());
        }

    }

    public function testNon200StatusCodesTriggerExceptions()
    {
        $smsBoxSmsProvider = new SmsBoxSmsProvider('someapikey', 'http://smsbox/api.php', false, $this->guzzleClientMock);

        $this->guzzleClientMock->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('http://smsbox/api.php'),
                $this->equalTo([
                    'headers' => ['Authorization' => 'App someapikey'],
                    'query' => [],
                    'form_params' => [
                        'dest' => '33611111111,33622222222,33633333333',
                        'msg' => 'some sweet non commercial text STOP SMS 36111',
                        'coding' => 'default',
                        'mode' => 'Expert',
                        'charset' => 'utf-8',
                        'origine' => 'confca',
                        'strategy' => 4,
                        'enable_short_url' => 'no',
                        'short_url_ssl' => 'yes'
                    ]
                ])
            )
            ->willReturn(new Response(500, [], 'I crashed etc'));
        try {
            $smsBoxSmsProvider->send([
                'to' => $this->recipients,
                'from' => 'conference-Khalil1608',
                'message' => 'some sweet non commercial text',
                'isCommercial' => true,
                'shortenUrls' => false
            ]);
            $this->fail('A 500 status code should trigger an exception');
        } catch (\Exception $e) {
            $this->assertEquals('SmsBox answered with status code 500 : I crashed etc', $e->getMessage());
        }

    }

    public function testSendForwardsExceptions()
    {
        $smsBoxSmsProvider = new SmsBoxSmsProvider('someapikey', 'http://smsbox/api.php', false, $this->guzzleClientMock);

        $this->guzzleClientMock->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo('http://smsbox/api.php'),
                $this->equalTo([
                    'headers' => ['Authorization' => 'App someapikey'],
                    'query' => [],
                    'form_params' => [
                        'dest' => '33611111111,33622222222,33633333333',
                        'msg' => 'some sweet non commercial text STOP SMS 36111',
                        'coding' => 'default',
                        'mode' => 'Expert',
                        'charset' => 'utf-8',
                        'origine' => 'EdeSanteNat',
                        'strategy' => 4,
                        'enable_short_url' => 'no',
                        'short_url_ssl' => 'yes'
                    ]
                ])
            )
            ->willThrowException(new \Exception('some app crash message'));
        try {
            $smsBoxSmsProvider->send([
                'to' => $this->recipients,
                'from' => 'ecole-sante-naturelle',
                'message' => 'some sweet non commercial text',
                'isCommercial' => true,
                'shortenUrls' => false
            ]);
            $this->fail('An exception should be forwarded');
        } catch (\Exception $e) {
            $this->assertEquals('some app crash message', $e->getMessage());
        }

    }


}
