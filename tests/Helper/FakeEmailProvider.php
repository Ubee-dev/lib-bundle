<?php

namespace UbeeDev\LibBundle\Tests\Helper;

use UbeeDev\LibBundle\Service\EmailProvider\EmailProviderInterface;
use UbeeDev\LibBundle\Service\Mailer;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class FakeEmailProvider implements EmailProviderInterface
{
    public function __construct(
        private readonly string $testToken,
        private readonly ParameterBagInterface $parameterBag) {
    }

    public function sendMail(
        string|array  $from,
        array   $to,
        string  $body,
        string  $subject,
        ?string $replyTo = null,
        string  $contentType = Mailer::HTML_CONTENT_TYPE,
        array   $attachments = []): array
    {

        $emailData = [
            'from' => $from,
            'body' => $body,
            'subject' => $subject,
            'replyTo' => $replyTo,
            'contentType' => $contentType,
            'attachments' => $attachments
        ];

        $fakeEmailsPath = $this->parameterBag->get('kernel.project_dir').'/var/fake-emails/'.$this->testToken.'/';
        $fileSystem = new Filesystem();

        if(!$fileSystem->exists($fakeEmailsPath)) {
            $fileSystem->mkdir($fakeEmailsPath);
        }

        $json = serialize($emailData);

        foreach ($to as $recipientEmail) {
            file_put_contents(
                $fakeEmailsPath.self::getFileNameForEmailAndSubject($recipientEmail, $subject),
                $json
            );
        }

        return $emailData;
    }

    public static function getFileNameForEmailAndSubject(string $email, string $subject): string
    {
        return $email.hash('sha256', $subject);
    }
}