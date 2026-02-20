<?php


namespace  UbeeDev\LibBundle\Tests\Consumer;

use UbeeDev\LibBundle\Consumer\EmailConsumer;
use UbeeDev\LibBundle\Service\Mailer;

class EmailConsumerTest extends AbstractConsumerCase
{
    /** @var EmailConsumer */
    private $emailConsumer;

    public function setUp(): void
    {
        parent::setUp();
        $this->emailConsumer = $this->initConsumer();
    }

    public function testSendEmailSuccessfully()
    {
        $this->createAMPMessage($parameters = [
            'from' => 'from@gmail.com',
            'to' => ['to@gmail.com'],
            'text' => 'some-text',
            'subject' => 'some-subject',
            'retryNumber' => 0,
            'attachments' => ['some-attachments']
        ], useSerialization: true);

        $this->mailerMock->expects($this->once())
            ->method('sendMail')
            ->with(
                $this->equalTo($parameters['from']),
                $this->equalTo($parameters['to']),
                $this->equalTo($parameters['text']),
                $this->equalTo($parameters['subject']),
                $this->equalTo(null),
                $this->equalTo(Mailer::HTML_CONTENT_TYPE),
                $this->equalTo($parameters['attachments'])
            );

        // Call the consumer, all mock expects should be green
        $this->emailConsumer->execute($this->message);
    }

    public function testSendEmailRetriesOnFirstFail()
    {
        $this->createAMPMessage($parameters = [
            'from' => 'from@gmail.com',
            'to' => ['to@gmail.com'],
            'text' => 'some-text',
            'subject' => 'some-subject',
            'retryNumber' => 0,
            'attachments' => ['some-attachments']
        ], useSerialization: true);

        $this->mailerMock->expects($this->once())
            ->method('sendMail')
            ->with(
                $this->equalTo($parameters['from']),
                $this->equalTo($parameters['to']),
                $this->equalTo($parameters['text']),
                $this->equalTo($parameters['subject']),
                $this->equalTo(null),
                $this->equalTo(Mailer::HTML_CONTENT_TYPE),
                $this->equalTo($parameters['attachments'])
            )->willThrowException(new \Exception('some error'));

        $this->emailProducerMock->expects($this->once())
            ->method('sendMail')
            ->with(
                $this->equalTo($parameters['from']),
                $this->equalTo($parameters['to']),
                $this->equalTo($parameters['text']),
                $this->equalTo($parameters['subject']),
                $this->equalTo($parameters['attachments']),
                $this->equalTo(1)
            );

        // Call the consumer, all mock expects should be green
        $this->emailConsumer->execute($this->message);
    }

    public function testSendEmailRetriesOnSecondFail()
    {
        $this->createAMPMessage($parameters = [
            'from' => 'from@gmail.com',
            'to' => ['to@gmail.com'],
            'text' => 'some-text',
            'subject' => 'some-subject',
            'retryNumber' => 1,
            'attachments' => ['some-attachments']
        ], useSerialization: true);

        $this->mailerMock->expects($this->once())
            ->method('sendMail')
            ->with(
                $this->equalTo($parameters['from']),
                $this->equalTo($parameters['to']),
                $this->equalTo($parameters['text']),
                $this->equalTo($parameters['subject']),
                $this->equalTo(null),
                $this->equalTo(Mailer::HTML_CONTENT_TYPE),
                $this->equalTo($parameters['attachments'])
            )->willThrowException(new \Exception('some error'));

        $this->emailProducerMock->expects($this->once())
            ->method('sendMail')
            ->with(
                $this->equalTo($parameters['from']),
                $this->equalTo($parameters['to']),
                $this->equalTo($parameters['text']),
                $this->equalTo($parameters['subject']),
                $this->equalTo($parameters['attachments']),
                $this->equalTo(2)
            );

        // Call the consumer, all mock expects should be green
        $this->emailConsumer->execute($this->message);
    }

    public function testSendEmailNotifyErrorOnThirdFail()
    {
        $this->createAMPMessage($parameters = [
            'from' => 'from@gmail.com',
            'to' => ['to@gmail.com'],
            'text' => 'some-text',
            'subject' => 'some-subject',
            'retryNumber' => 2,
            'attachments' => ['some-attachments']
        ], useSerialization: true);

        $this->mailerMock->expects($this->once())
            ->method('sendMail')
            ->with(
                $this->equalTo($parameters['from']),
                $this->equalTo($parameters['to']),
                $this->equalTo($parameters['text']),
                $this->equalTo($parameters['subject']),
                $this->equalTo(null),
                $this->equalTo(Mailer::HTML_CONTENT_TYPE),
                $this->equalTo($parameters['attachments'])
            )->willThrowException(new \Exception('some error'));

        $this->emailProducerMock->expects($this->never())
            ->method('sendMail');

        unset($parameters['text']);
        $this->errorProducerMock->expects($this->once())
            ->method('sendNotification')
            ->with(
                $this->equalTo(EmailConsumer::class),
                $this->equalTo('sendMail'),
                $this->equalTo($parameters),
                $this->equalTo('some error')
            );

        // Call the consumer, all mock expects should be green
        $this->emailConsumer->execute($this->message);
    }

    private function initConsumer(): EmailConsumer
    {
        return new EmailConsumer(
            $this->mailerMock,
            $this->errorProducerMock,
            $this->emailProducerMock
        );
    }
}
