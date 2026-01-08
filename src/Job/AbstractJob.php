<?php

namespace Khalil1608\LibBundle\Job;

use Khalil1608\LibBundle\Service\Mailer;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractJob
{
    /** @var string */
    protected $projectName;

    /** @var array */
    protected $options;

    /** @var OutputInterface */
    protected $output;

    /** @var Mailer */
    protected $mailer;

    /**
     * @return string Used in mail reports on failures
     */
    abstract public function getProjectName();

    abstract public function run();

    /**
     * @param array $options
     * @param OutputInterface $output
     * @param Mailer $mailer
     */
    public function __construct(array $options, OutputInterface $output, $mailer)
    {
        $this->options = $options;
        $this->output = $output;
        $this->mailer = $mailer;
        $this->projectName = $this->getProjectName();
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Write job exception out to console and send error report.
     *
     * @param $error_message
     * @param \Exception $e
     * @param OutputInterface $output
     */
    protected function handleJobException($error_message, \Exception $e, OutputInterface $output)
    {
        $output->writeln("<error>$error_message</error>");
        $output->writeln($e->getFile() . ':' . $e->getLine() . ' ' . $e->getMessage());

        try {
            $this->sendErrorReport($e->getFile() . ':' . $e->getLine() . ' ' . $e->getMessage());
        } catch (\Exception $e) {
            // Failure to send error report email will be output to console
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }

    /**
     * Send error report by email.
     *
     * @param $error_message
     * @param \Exception $e
     * @throws \Exception If report was not sent
     */
    protected function sendErrorReport($error_message, ?\Exception $e = null)
    {
        $error_body = $this->projectName . ' ' . $this->getCommandName() . "\n\n";
        $error_body .= $error_message;
        if ($e) {
            $error_body .= "\n\nThrown exception:\n" . $e->getMessage();
        }

        $subject = $this->projectName . ' ' . $this->getCommandName() . ' ' . $error_message;

        $to = [
            'khalil1608@gmail.com',
        ];


        $this->mailer->sendMail('khalil1608@gmail.com', $to, $error_body, $subject);
    }

    /**
     * @param string $exeptionMessage
     * @param string $errorMessage
     * @param string $commandName
     */
    protected function showErrorMessage($exeptionMessage, $errorMessage, $commandName = '')
    {
        $this->output->writeln("<error>$errorMessage</error>");
        $this->output->writeln($exeptionMessage);
        try {
            $this->sendErrorReport($errorMessage . "\n" . $exeptionMessage, $commandName);
        } catch (\Exception $e) {
            // Failure to send error report email will be output to console
            $this->output->writeln("<error>{$e->getMessage()}</error>");
        }
    }

    /**
     * Send error report by email.
     *
     * @param array $to
     * @param string $body
     * @throws \Exception If report was not sent
     */
    protected function sendReport($to, $subject, $body)
    {
        $mailer = $this->mailer;
        $mailer->sendMail('khalil1608@gmail.com', $to, $body, $subject);
    }

    protected function getCommandName()
    {
        $jobClass = get_class($this);
        return preg_match("/([a-zA-Z]+)Job$/", $jobClass, $match)  ? $match[1] : get_class($this);
    }
}
