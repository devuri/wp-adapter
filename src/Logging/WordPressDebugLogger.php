<?php

declare(strict_types=1);

namespace AdapterKit\Core\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

final class WordPressDebugLogger extends AbstractLogger
{
    private string $minimumLevel;

    private static array $levels = [
        LogLevel::DEBUG     => 0,
        LogLevel::INFO      => 1,
        LogLevel::NOTICE    => 2,
        LogLevel::WARNING   => 3,
        LogLevel::ERROR     => 4,
        LogLevel::CRITICAL  => 5,
        LogLevel::ALERT     => 6,
        LogLevel::EMERGENCY => 7,
    ];

    public function __construct(string $minimumLevel = LogLevel::DEBUG)
    {
        $this->minimumLevel = $minimumLevel;
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = array()): void
    {
        if (!defined('WP_DEBUG_LOG') || !WP_DEBUG_LOG) {
            return;
        }

        $levelKey   = isset(self::$levels[(string) $level]) ? (string) $level : LogLevel::DEBUG;
        $minimumKey = isset(self::$levels[$this->minimumLevel]) ? $this->minimumLevel : LogLevel::DEBUG;

        if (self::$levels[$levelKey] < self::$levels[$minimumKey]) {
            return;
        }

        $formatted = strtoupper((string) $level) . ': ' . $this->interpolate($message, $context);
        error_log($formatted);
    }

    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = (string) $val;
            }
        }
        return strtr($message, $replace);
    }
}
