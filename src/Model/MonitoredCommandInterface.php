<?php

namespace UbeeDev\LibBundle\Model;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface MonitoredCommandInterface
{
    public function perform(InputInterface $input, OutputInterface $output): void;
    public function getMonitoringSlug(): string;
}