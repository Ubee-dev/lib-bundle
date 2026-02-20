<?php

namespace UbeeDev\LibBundle\Tests\Service;

use UbeeDev\LibBundle\Service\EmailProvider\EmailProviderInterface;
use UbeeDev\LibBundle\Service\Mailer;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class MailerTest extends AbstractWebTestCase
{
    private Mailer $mailerService;
    private EmailProviderInterface|MockObject $emailProviderMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailProviderMock = $this->getMockedClass(EmailProviderInterface::class);
        $this->mailerService = new Mailer($this->emailProviderMock);
    }



    public function testIfMailIsSentSuccessfully()
    {
        $from = 'contact@domain.fr';
        $to = ['test@example.com'];
        $body = 'super body';
        $subject = 'super subject';
        $replyTo = 'replyTo@domain.fr';
        $contentType = Mailer::HTML_CONTENT_TYPE;
        $attachments = ['some' => 'attachments'];

        $this->emailProviderMock->expects($this->once())
            ->method('sendMail')
            ->with(
                $this->equalTo($from),
                $this->equalTo($to),
                $this->equalTo($body),
                $this->equalTo($subject),
                $this->equalTo($replyTo),
                $this->equalTo($contentType),
                $this->equalTo($attachments),
            );

        $this->mailerService->sendMail(
            $from,
            $to,
            $body,
            $subject,
            $replyTo,
            $contentType,
            $attachments
        );
    }
}
