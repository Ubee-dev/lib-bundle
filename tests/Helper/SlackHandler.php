<?php

namespace Khalil1608\LibBundle\Tests\Helper;

use Monolog\LogRecord;

class SlackHandler extends \Monolog\Handler\SlackHandler
{
    /**
     * @return string[]
     */
    protected function prepareContentData(LogRecord $record): array
    {
        $dataArray = parent::prepareContentData($record);
        if($record->extra['threadTs']) {
            $dataArray['thread_ts'] = $record->extra['threadTs'];
        }

        return $dataArray;
    }
}
