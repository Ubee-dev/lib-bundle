<?php

namespace Khalil1608\LibBundle\Tests\Service\EmailProvider\Mailchimp;

use Khalil1608\LibBundle\Service\EmailProvider\BulkEmailProviderInterface;
use Khalil1608\LibBundle\Service\EmailProvider\Mailchimp\AbstractEmailProvider;
use Khalil1608\LibBundle\Service\EmailProvider\Mailchimp\BulkEmailProvider;
use Khalil1608\LibBundle\Tests\AbstractWebTestCase;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class BulkEmailProviderTest extends AbstractWebTestCase
{
    private BulkEmailProvider $bulkEmailProvider;
    private MockObject|HttpClientInterface $httpClientMock;
    private string $apiKey = 'some_key';

    public function setUp(): void
    {
        parent::setUp();
        $this->httpClientMock = $this->getMockedClass(HttpClientInterface::class);
        $this->initProvider();
    }

    public function testConstructorAndProperties(): void
    {
        $this->assertInstanceOf(BulkEmailProviderInterface::class, $this->bulkEmailProvider);
    }

    /**
     * @return void
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testSendBulkEmailSuccessfully(): void
    {
        $html = '<h1>Hello World!</h1><p>To boldly go where no one has gone before.</p>';
        $subject = 'Hello World';
        $from = 'toto@gmail.com';
        $fromName = 'Toto Joke';
        $tags = ['some' => 'tags'];

        $attachmentFile = $this->getUploadedFile();

        $this->expectMailchimpApiShouldBeCalled(
            url: '/messages/send',
            params: [
                'key' => $this->apiKey,
                'message' => [
                    'html' => $html,
                    'subject' => $subject,
                    'from_email' => $from,
                    'from_name' => $fromName,
                    'to' => [
                        [
                            'email' => 'alice@wonderlande.com',
                            'name' => 'Alice Wonderland',
                            'type' => 'to'
                        ],
                        [
                            'email' => 'jim@hawkins.com',
                            'name' => 'Jim Hawkins',
                            'type' => 'to'
                        ],

                    ],
                    'headers' => [
                        'Reply-To' => $from
                    ],
                    'auto_text' => true,
                    'auto_html' => true,
                    'preserve_recipients' => false,
                    'merge' => true,
                    'merge_language' => 'mailchimp',
                    'merge_vars' => [
                        [
                            'rcpt' => 'alice@wonderlande.com',
                            'vars' => [
                                ['name' => 'firstName', 'content' => 'Alice'],
                                ['name' => 'lastName', 'content' => 'Wonderland'],
                                ['name' => 'var1', 'content' => 'toto'],
                                ['name' => 'var2', 'content' => 'titi'],
                            ]
                        ],
                        [
                            'rcpt' => 'jim@hawkins.com',
                            'vars' => [
                                ['name' => 'firstName', 'content' => 'Jim'],
                                ['name' => 'lastName', 'content' => 'Hawkins'],
                                ['name' => 'var1', 'content' => 'tutu'],
                                ['name' => 'var2', 'content' => 'tata'],
                            ]
                        ]
                    ],
                    'tags' => $tags,
                    'attachments' => [
                        [
                            'content' => base64_encode('toto@gmail.com'),
                            'type' => 'text/csv',
                            'name' => 'some-csv.csv',
                        ],
                        [
                            'content' => base64_encode(file_get_contents($attachmentFile->getFileInfo()->getPathname())),
                            'type' => 'application/pdf',
                            'name' => 'document.pdf',
                        ],

                    ],
                ],
                'async' => true,
            ],
            returnedValue: $returnedValue = ['ok']
        );

        $response = $this->bulkEmailProvider->send([
            'html' => $html,
            'subject' => $subject,
            'fromEmail' => $from,
            'fromName' => $fromName,
            'recipients' => [
                [
                    'email' => 'alice@wonderlande.com',
                    'firstName' => 'Alice',
                    'lastName' => 'Wonderland',
                    'var1' => 'toto',
                    'var2' => 'titi',
                ],
                [
                    'email' => 'jim@hawkins.com',
                    'firstName' => 'Jim',
                    'lastName' => 'Hawkins',
                    'var1' => 'tutu',
                    'var2' => 'tata',
                ]
            ],
            'replyTo' => $from,
            'tags' => $tags,
            'extraVars' => ['var1', 'var2'],
            'attachments' =>  [
                [
                    'content' => 'toto@gmail.com',
                    'type' => 'text/csv',
                    'name' => 'some-csv.csv',
                ],
                $attachmentFile,

            ],
        ]);

        $this->assertEquals($returnedValue, $response);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testSendThrowsWhenOptionsContractIsBroken(): void
    {
        try {
            $this->bulkEmailProvider->send([
                'html' => '<h1>Hello World!</h1><p>To boldly go where no one has gone before.</p>',
                'subject' => 'Hello World',
            ]);
            $this->fail('Expected Exception not thrown.');
        } catch (Exception $e) {
            $this->assertEquals(
                'BulkEmailProvider expects options [html, subject, fromEmail, fromName, recipients, replyTo, tags, extraVars, attachments], but received [html, subject].',
                $e->getMessage()
            );
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testSendDoesNothingIfNoRecipientsGiven(): void
    {
        try {
            $result = $this->bulkEmailProvider->send([
                'html' => '<h1>Hello World!</h1><p>To boldly go where no one has gone before.</p>',
                'subject' => 'Hello World',
                'fromEmail' => 'jim@hawkins.com',
                'fromName' => 'Jim Hawkins',
                'recipients' => [],
                'replyTo' => 'jim@hawkins.com',
                'tags' => [],
                'extraVars' => [],
                'attachments' => [],
            ]);

            $this->assertEquals([], $result);
        } catch (Exception $e) {
            $this->fail('Should not throw.');
        }
    }

    private function expectMailchimpApiShouldBeCalled(string $url, array $params, array $returnedValue): void
    {
        $responseMock = $this->mockJsonResponse($returnedValue);
        $this->httpClientMock->expects($this->once())
            ->method('request')
            ->with($this->equalTo('POST'),
                $this->equalTo(AbstractEmailProvider::API_URL.$url),
                $this->equalTo([
                    'body' => $params
                ]))
            ->willReturn($responseMock);
    }

    /**
     * @param $responseData
     * @return MockObject
     */
    private function mockJsonResponse($responseData): MockObject
    {
        $responseMock = $this->getMockedClass(ResponseInterface::class);
        $responseMock->expects($this->any())
            ->method('getContent')
            ->willReturn(json_encode($responseData));

        return $responseMock;
    }

    private function initProvider(): void
    {
        $this->bulkEmailProvider = new BulkEmailProvider(
            apiKey: $this->apiKey,
            client: $this->httpClientMock,
        );
    }
}