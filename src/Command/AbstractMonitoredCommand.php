<?php

namespace UbeeDev\LibBundle\Command;

use Exception;
use UbeeDev\LibBundle\Model\MonitoredCommandInterface;
use Sentry\CheckInStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Sentry\captureCheckIn;
use function Sentry\captureException;

abstract class AbstractMonitoredCommand extends Command implements MonitoredCommandInterface
{
    protected function configure(): void
    {
        parent::configure();
        $this
            ->addOption(
                'monitoring-slug',
                null,
                InputOption::VALUE_REQUIRED,
                "Slug passed to monitoring tool. Use only if you execute the same command multiple times.",
                default: $this->getMonitoringSlug()

            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $slug = $input->getOption('monitoring-slug');
            // 🟡 Notify Sentry your job is running:
            $checkInId = captureCheckIn(
                slug: $slug,
                status: CheckInStatus::inProgress()
            );

            $this->perform($input, $output);

            captureCheckIn(
                slug: $slug,
                status: CheckInStatus::ok(),
                checkInId: $checkInId,
            );

            return Command::SUCCESS;
        } catch (Exception $exception) {
            captureException($exception);
            captureCheckIn(
                slug: $slug,
                status: CheckInStatus::error(),
                checkInId: $checkInId,
            );

            $output->writeln("<error>{$exception->getMessage()}</error>");

            if ($output->isVerbose()) {
                $output->writeln("<comment>{$exception->getTraceAsString()}</comment>");
            }

            return Command::FAILURE;
        }
    }

    public function getMonitoringSlug(): string
    {
        return str_replace(':', '_', strtolower($this->getName()));
    }
}