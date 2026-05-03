<?php

declare(strict_types=1);

namespace AdapterKit\Core\Environment;

use AdapterKit\Core\Contracts\EnvironmentInterface;

final class WordPressEnvironment implements EnvironmentInterface
{
    public function homeUrl(string $path = ''): string
    {
        return home_url($path);
    }

    public function adminUrl(string $path = ''): string
    {
        return admin_url($path);
    }

    /**
     * @return int|string
     */
    public function currentTime(string $type)
    {
        return current_time($type);
    }

    public function sanitizeTextField(string $value): string
    {
        return sanitize_text_field($value);
    }

    public function sanitizeKey(string $key): string
    {
        return sanitize_key($key);
    }

    public function escHtml(string $value): string
    {
        return esc_html($value);
    }

    public function escUrl(string $url): string
    {
        return esc_url($url);
    }

    public function escAttr(string $value): string
    {
        return esc_attr($value);
    }

    public function wpKsesPost(string $value): string
    {
        return wp_kses_post($value);
    }

    public function getCurrentScreenId(): ?string
    {
        if (!function_exists('get_current_screen')) {
            return null;
        }
        $screen = get_current_screen();
        return $screen ? $screen->id : null;
    }
}
