<?php

declare(strict_types=1);

namespace AdapterKit\Core\Contracts;

interface EnvironmentInterface
{
    public function homeUrl(string $path = ''): string;

    public function adminUrl(string $path = ''): string;

    /**
     * @return int|string
     */
    public function currentTime(string $type);

    public function sanitizeTextField(string $value): string;

    public function sanitizeKey(string $key): string;

    public function escHtml(string $value): string;

    public function escUrl(string $url): string;

    public function escAttr(string $value): string;

    public function wpKsesPost(string $value): string;

    public function getCurrentScreenId(): ?string;
}
