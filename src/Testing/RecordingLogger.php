<?php

declare(strict_types=1);

namespace AdapterKit\Core\Testing;

use Psr\Log\AbstractLogger;

final class RecordingLogger extends AbstractLogger
{
    /** @var array<int, array{level: string, message: string, context: array}> */
    private array $entries = [];

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = array()): void
    {
        $this->entries[] = [
            'level'   => (string) $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }

    public function hasWarning(string $message): bool
    {
        return $this->hasEntryWithLevel('warning', $message);
    }

    public function hasError(string $message): bool
    {
        return $this->hasEntryWithLevel('error', $message);
    }

    public function hasInfo(string $message): bool
    {
        return $this->hasEntryWithLevel('info', $message);
    }

    public function hasDebug(string $message): bool
    {
        return $this->hasEntryWithLevel('debug', $message);
    }

    /**
     * @return array<int, array{level: string, message: string, context: array}>
     */
    public function getErrors(): array
    {
        return $this->getEntriesByLevel('error');
    }

    /**
     * @return array<int, array{level: string, message: string, context: array}>
     */
    public function getWarnings(): array
    {
        return $this->getEntriesByLevel('warning');
    }

    /**
     * @return array<int, array{level: string, message: string, context: array}>
     */
    public function all(): array
    {
        return $this->entries;
    }

    public function count(string $level): int
    {
        return count($this->getEntriesByLevel($level));
    }

    public function clear(): void
    {
        $this->entries = [];
    }

    private function hasEntryWithLevel(string $level, string $message): bool
    {
        foreach ($this->entries as $entry) {
            if ($entry['level'] === $level && strpos($entry['message'], $message) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array<int, array{level: string, message: string, context: array}>
     */
    private function getEntriesByLevel(string $level): array
    {
        return array_values(array_filter(
            $this->entries,
            static function (array $entry) use ($level): bool {
                return $entry['level'] === $level;
            }
        ));
    }
}
