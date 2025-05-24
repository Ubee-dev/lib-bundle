<?php

namespace Khalil1608\LibBundle\Command;

use App\Job\AbstractJob;
use App\Job\ScheduleEventRemindersJob;
use Khalil1608\LibBundle\Entity\DateTime;
use Khalil1608\LibBundle\Model\MonitoredCommandInterface;
use Sentry\CheckInStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
            $checkInId = \Sentry\captureCheckIn(
                slug: $slug,
                status: CheckInStatus::inProgress()
            );

            $this->perform($input, $output);

            \Sentry\captureCheckIn(
                slug: $slug,
                status: CheckInStatus::ok(),
                checkInId: $checkInId,
            );

            return Command::SUCCESS;
        } catch (\Exception $exception) {
            captureException($exception);
            \Sentry\captureCheckIn(
                slug: $slug,
                status: CheckInStatus::error(),
                checkInId: $checkInId,
            );
            return Command::FAILURE;
        }
    }

    public function getMonitoringSlug(): string
    {
        return str_replace(':', '_', strtolower($this->getName()));
    }
}