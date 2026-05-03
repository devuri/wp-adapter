<?php

declare(strict_types=1);

namespace ExamplePlugin;

use AdapterKit\Core\Contracts\HttpClientInterface;
use AdapterKit\Core\Contracts\OptionStorageInterface;
use AdapterKit\Core\Result;
use Psr\Log\LoggerInterface;

/**
 * Pure business logic. No WordPress functions.
 * Receives every collaborator through the constructor as a contract.
 * Fully unit-testable without WordPress.
 */
final class LicenseService
{
    private OptionStorageInterface $options;
    private HttpClientInterface    $http;
    private LoggerInterface        $logger;
    private string                 $optionKey;

    public function __construct(
        OptionStorageInterface $options,
        HttpClientInterface    $http,
        LoggerInterface        $logger,
        string                 $optionKey
    ) {
        $this->options   = $options;
        $this->http      = $http;
        $this->logger    = $logger;
        $this->optionKey = $optionKey;
    }

    public function activate(string $licenseKey): Result
    {
        $response = $this->http->post('https://api.example.com/activate', [
            'body' => ['key' => $licenseKey],
        ]);

        if ($response['is_error']) {
            $this->logger->warning('activation_failed', [
                'reason' => $response['error_message'],
            ]);
            return Result::failure('activation_failed', (string) $response['error_message']);
        }

        $this->options->update($this->optionKey, [
            'active' => true,
            'key'    => $licenseKey,
        ]);

        return Result::success(['active' => true]);
    }

    public function deactivate(): Result
    {
        $this->options->update($this->optionKey, ['active' => false, 'key' => '']);
        $this->logger->info('license_deactivated', []);
        return Result::success();
    }

    public function isActive(): bool
    {
        $stored = $this->options->get($this->optionKey, []);
        return is_array($stored) && !empty($stored['active']);
    }
}
