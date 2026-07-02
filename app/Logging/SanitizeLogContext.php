<?php

namespace App\Logging;

use App\Support\Logging\SensitiveLogContext;
use Monolog\Logger;
use Monolog\LogRecord;

class SanitizeLogContext
{
    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(function (LogRecord $record): LogRecord {
            return $record->with(
                message: SensitiveLogContext::sanitizeMessage($record->message),
                context: SensitiveLogContext::sanitize($record->context),
                extra: SensitiveLogContext::sanitize($record->extra)
            );
        });
    }
}
