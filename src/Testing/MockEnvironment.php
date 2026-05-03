<?php

declare(strict_types=1);

namespace AdapterKit\Core\Testing;

use AdapterKit\Core\Contracts\EnvironmentInterface;

final class MockEnvironment implements EnvironmentInterface
{
    private string $homeUrl;
    private string $adminUrl;
    private int $timestamp;
    private ?string $currentScreenId = null;

    public function __construct(string $homeUrl, string $adminUrl, int $timestamp)
    {
        $this->homeUrl   = rtrim($homeUrl, '/');
        $this->adminUrl  = rtrim($adminUrl, '/');
        $this->timestamp = $timestamp;
    }

    public function homeUrl(string $path = ''): string
    {
        return $path !== '' ? $this->homeUrl . '/' . ltrim($path, '/') : $this->homeUrl;
    }

    public function adminUrl(string $path = ''): string
    {
        return $path !== '' ? $this->adminUrl . '/' . ltrim($path, '/') : $this->adminUrl;
    }

    /**
     * @return int|string
     */
    public function currentTime(string $type)
    {
        if ($type === 'timestamp' || $type === 'U') {
            return $this->timestamp;
        }
        return date($type, $this->timestamp);
    }

    public function sanitizeTextField(string $value): string
    {
        return trim(strip_tags($value));
    }

    public function sanitizeKey(string $key): string
    {
        return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $key));
    }

    public function escHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public function escUrl(string $url): string
    {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }

    public function escAttr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public function wpKsesPost(string $value): string
    {
        return strip_tags($value, '<p><a><strong><em><ul><ol><li><br><hr>');
    }

    public function getCurrentScreenId(): ?string
    {
        return $this->currentScreenId;
    }

    public function setCurrentScreenId(?string $id): void
    {
        $this->currentScreenId = $id;
    }
}
