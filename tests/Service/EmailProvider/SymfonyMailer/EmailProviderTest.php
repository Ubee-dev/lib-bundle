<?php

declare(strict_types=1);

namespace UbeeDev\LibBundle\Tests\Service\EmailProvider\SymfonyMailer;

use UbeeDev\LibBundle\Service\EmailProvider\SymfonyMailer\EmailProvider;
use UbeeDev\LibBundle\Service\Mailer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class EmailProviderTest extends TestCase
{
    private MailerInterface&MockObject $mailerMock;
    private EmailProvider $provider;

    protected function setUp(): void
    {
        $this->mailerMock = $this->createMock(MailerInterface::class);
        $this->provider = new EmailProvider($this->mailerMock);
    }

    public function testSendHtmlEmail(): void
    {
        $this->mailerMock
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                $this->assertEquals([new Address('noreply@games-on.com')], $email->getFrom());
                $this->assertEquals([new Address('player@test.com')], $email->getTo());
                $this->assertSame('Welcome!', $email->getSubject());
                $this->assertSame('<h1>Hello</h1>', $email->getHtmlBody());
                $this->assertNull($email->getTextBody());

                return true;
            }));

        $result = $this->provider->sendMail(
            from: 'noreply@games-on.com',
            to: ['player@test.com'],
            body: '<h1>Hello</h1>',
            subject: 'Welcome!',
        );

        $this->assertSame(['success' => true], $result);
    }

    public function testSendTextEmail(): void
    {
        $this->mailerMock
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                $this->assertSame('Plain text body', $email->getTextBody());
                $this->assertNull($email->getHtmlBody());

                return true;
            }));

        $this->provider->sendMail(
            from: 'noreply@games-on.com',
            to: ['player@test.com'],
            body: 'Plain text body',
            subject: 'Test',
            contentType: Mailer::TEXT_CONTENT_TYPE,
        );
    }

    public function testSendWithArrayFrom(): void
    {
        $this->mailerMock
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                $from = $email->getFrom();
                $this->assertCount(1, $from);
                $this->assertSame('noreply@games-on.com', $from[0]->getAddress());
                $this->assertSame('Games On', $from[0]->getName());

                return true;
            }));

        $this->provider->sendMail(
            from: ['email' => 'noreply@games-on.com', 'name' => 'Games On'],
            to: ['player@test.com'],
            body: '<p>Hi</p>',
            subject: 'Test',
        );
    }

    public function testSendWithMultipleRecipients(): void
    {
        $this->mailerMock
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                $to = $email->getTo();
                $this->assertCount(2, $to);
                $this->assertSame('alice@test.com', $to[0]->getAddress());
                $this->assertSame('bob@test.com', $to[1]->getAddress());

                return true;
            }));

        $this->provider->sendMail(
            from: 'noreply@games-on.com',
            to: ['alice@test.com', 'bob@test.com'],
            body: '<p>Hi</p>',
            subject: 'Test',
        );
    }

    public function testSendWithReplyTo(): void
    {
        $this->mailerMock
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                $replyTo = $email->getReplyTo();
                $this->assertCount(1, $replyTo);
                $this->assertSame('reply@games-on.com', $replyTo[0]->getAddress());

                return true;
            }));

        $this->provider->sendMail(
            from: 'noreply@games-on.com',
            to: ['player@test.com'],
            body: '<p>Hi</p>',
            subject: 'Test',
            replyTo: 'reply@games-on.com',
        );
    }

    public function testSendWithoutReplyTo(): void
    {
        $this->mailerMock
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                $this->assertEmpty($email->getReplyTo());

                return true;
            }));

        $this->provider->sendMail(
            from: 'noreply@games-on.com',
            to: ['player@test.com'],
            body: '<p>Hi</p>',
            subject: 'Test',
        );
    }

    public function testSendWithAttachments(): void
    {
        $this->mailerMock
            ->expects($this->once())
            ->method('send')
            ->with($this->callback(function (Email $email): bool {
                $attachments = $email->getAttachments();
                $this->assertCount(1, $attachments);
                $this->assertSame('report.pdf', $attachments[0]->getFilename());

                return true;
            }));

        $this->provider->sendMail(
            from: 'noreply@games-on.com',
            to: ['player@test.com'],
            body: '<p>See attached</p>',
            subject: 'Report',
            attachments: [
                ['content' => 'fake-pdf-content', 'name' => 'report.pdf'],
            ],
        );
    }

    public function testSendReturnsFailureOnMailerException(): void
    {
        $this->mailerMock
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new TransportException('Connection refused'));

        $result = $this->provider->sendMail(
            from: 'noreply@games-on.com',
            to: ['player@test.com'],
            body: '<p>Hi</p>',
            subject: 'Test',
        );

        $this->assertSame(false, $result['success']);
        $this->assertSame('Connection refused', $result['error']);
    }
}
