<?php


namespace UbeeDev\LibBundle\Tests\Producer;

use UbeeDev\LibBundle\Producer\EmailProducer;

class EmailProducerTest extends AbstractProducerCase
{

    public function testSendEmailWithoutRetryNumber()
    {
        $messageBody = serialize([
            'from' => 'from@gmail.com',
            'to' => ['to@gmail.com'],
            'text' => 'some-text',
            'subject' => 'some-subject',
            'attachments' => ['some-attachments'],
            'retryNumber' => 0,
            'replyTo' => null
        ]);

        $this->producerMock->expects($this->once())
            ->method('publish')
            ->with(
                $this->equalTo($messageBody),
                $this->equalTo(''),
                $this->equalTo([]),
                $this->equalTo([])
            );

        $emailProducer = new EmailProducer($this->producerMock);

        $emailProducer->sendMail('from@gmail.com', ['to@gmail.com'], 'some-text', 'some-subject', ['some-attachments']);
    }

    public function testSendEmailWithOneRetryNumber()
    {
        $messageBody = serialize([
            'from' => 'from@gmail.com',
            'to' => ['to@gmail.com'],
            'text' => 'some-text',
            'subject' => 'some-subject',
            'attachments' => ['some-attachments'],
            'retryNumber' => 1,
            'replyTo' => null
        ]);

        $this->producerMock->expects($this->once())
            ->method('publish')
            ->with(
                $this->equalTo($messageBody),
                $this->equalTo(''),
                $this->equalTo([]),
                $this->equalTo(['x-delay' => 5 * 60 * 1000])
            );

        $emailProducer = new EmailProducer($this->producerMock);

        $emailProducer->sendMail('from@gmail.com', ['to@gmail.com'], 'some-text', 'some-subject', ['some-attachments'],1);
    }

    public function testSendEmailWithTwoRetryNumber()
    {
        $messageBody = serialize([
            'from' => 'from@gmail.com',
            'to' => ['to@gmail.com'],
            'text' => 'some-text',
            'subject' => 'some-subject',
            'attachments' => ['some-attachments'],
            'retryNumber' => 2,
            'replyTo' => null
        ]);

        $this->producerMock->expects($this->once())
            ->method('publish')
            ->with(
                $this->equalTo($messageBody),
                $this->equalTo(''),
                $this->equalTo([]),
                $this->equalTo(['x-delay' => 15 * 60 * 1000])
            );

        $emailProducer = new EmailProducer($this->producerMock);

        $emailProducer->sendMail('from@gmail.com', ['to@gmail.com'], 'some-text', 'some-subject', ['some-attachments'],2);
    }
}
