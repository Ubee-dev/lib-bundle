<?php

namespace Khalil1608\LibBundle\Tests\Helper;

use Monolog\Level;
use Monolog\LogRecord;

class MonologCISlackHandler extends SlackHandler
{
    public function __construct(
        private readonly string $isCi,
        private readonly string $slackNotificationTs,
                                $token,
                                $channel,
                                $level = Level::Debug,
        bool                    $bubble = true
    )
    {
        parent::__construct(
            token: $token,
            channel: $channel,
            level: $level,
            bubble: $bubble,
        );
    }

    protected function write(LogRecord $record): void
    {
        if ($this->isCi === 'true') {
            $record->extra['threadTs'] = $this->slackNotificationTs;
            parent::write($record);
        }
    }
}