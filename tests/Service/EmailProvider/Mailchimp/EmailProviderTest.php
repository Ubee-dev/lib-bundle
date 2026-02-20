<?php

namespace UbeeDev\LibBundle\Tests\Service\EmailProvider\Mailchimp;

use UbeeDev\LibBundle\Service\EmailProvider\EmailProviderInterface;
use UbeeDev\LibBundle\Service\EmailProvider\Mailchimp\AbstractEmailProvider;
use UbeeDev\LibBundle\Service\EmailProvider\Mailchimp\EmailProvider;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class EmailProviderTest extends AbstractWebTestCase
{
    private EmailProvider $emailProvider;
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
        $this->assertInstanceOf(EmailProviderInterface::class, $this->emailProvider);
    }

    /**
     * @return void
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testSendEmailWithHtmlSuccessfully(): void
    {
        $html = '<h1>Hello World!</h1><p>To boldly go where no one has gone before.</p>';
        $subject = 'Hello World';
        $from = 'toto@gmail.com';
        $replyTo = 'titi@gmail.com';
        $attachmentFile = $this->getUploadedFile();

        $this->expectMailchimpApiShouldBeCalled(
            url: '/messages/send',
            params: [
                'key' => $this->apiKey,
                'message' => [
                    'html' => $html,
                    'subject' => $subject,
                    'from_email' => $from,
                    'to' => [
                        [
                            'email' => 'alice@wonderlande.com',
                            'name' => 'alice@wonderlande.com',
                            'type' => 'to'
                        ],
                        [
                            'email' => 'jim@hawkins.com',
                            'name' => 'jim@hawkins.com',
                            'type' => 'to'
                        ],
                    ],
                    'headers' => [
                        'Reply-To' => $replyTo
                    ],
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
                'async' => false,
            ],
            returnedValue: $returnedValue = ['ok']
        );

        $response = $this->emailProvider->sendMail(
            from: $from,
            to: ['alice@wonderlande.com', 'jim@hawkins.com'],
            body: $html,
            subject: $subject,
            replyTo: $replyTo,
            attachments: [
                [
                    'content' => 'toto@gmail.com',
                    'type' => 'text/csv',
                    'name' => 'some-csv.csv',
                ],
                $attachmentFile,
            ]
        );

        $this->assertEquals($returnedValue, $response);
    }

    /**
     * @return void
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function testSendEmailWithOtherContentTypeWithoutAttachmentAndWithoutReplyToSuccessfully(): void
    {
        $html = '<h1>Hello World!</h1><p>To boldly go where no one has gone before.</p>';
        $subject = 'Hello World';
        $from = 'toto@gmail.com';

        $this->expectMailchimpApiShouldBeCalled(
            url: '/messages/send',
            params: [
                'key' => $this->apiKey,
                'message' => [
                    'text' => $html,
                    'subject' => $subject,
                    'from_email' => $from,
                    'to' => [
                        [
                            'email' => 'alice@wonderlande.com',
                            'name' => 'alice@wonderlande.com',
                            'type' => 'to'
                        ],
                        [
                            'email' => 'jim@hawkins.com',
                            'name' => 'jim@hawkins.com',
                            'type' => 'to'
                        ],
                    ],
                    'attachments' => [],
                ],
                'async' => false,
            ],
            returnedValue: ['ok']
        );

        $this->emailProvider->sendMail(
            from: $from,
            to: ['alice@wonderlande.com', 'jim@hawkins.com'],
            body: $html,
            subject: $subject,
            contentType: 'text',
        );
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
        $this->emailProvider = new EmailProvider(
            apiKey: $this->apiKey,
            client: $this->httpClientMock,
        );
    }
}