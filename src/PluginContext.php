<?php

declare(strict_types=1);

namespace AdapterKit\Core;

/**
 * Immutable plugin metadata. Bootstrap-edge helper.
 * fromPluginFile() calls WordPress functions and is the only approved
 * non-adapter exception to the "WordPress calls only in adapters" rule.
 */
final class PluginContext
{
    private string $slug;
    private string $version;
    private string $file;
    private string $basename;
    private string $dirPath;
    private string $dirUrl;
    private string $textDomain;
    private string $optionPrefix;

    private function __construct(
        string $slug,
        string $version,
        string $file,
        string $basename,
        string $dirPath,
        string $dirUrl,
        string $textDomain,
        string $optionPrefix
    ) {
        $this->slug         = $slug;
        $this->version      = $version;
        $this->file         = $file;
        $this->basename     = $basename;
        $this->dirPath      = $dirPath;
        $this->dirUrl       = $dirUrl;
        $this->textDomain   = $textDomain;
        $this->optionPrefix = $optionPrefix;
    }

    public static function fromPluginFile(
        string $file,
        string $slug,
        string $version,
        string $textDomain,
        string $optionPrefix
    ): self {
        return new self(
            $slug,
            $version,
            $file,
            plugin_basename($file),
            plugin_dir_path($file),
            plugin_dir_url($file),
            $textDomain,
            $optionPrefix
        );
    }

    /**
     * For use in unit tests or environments where WordPress is not loaded.
     */
    public static function fromValues(
        string $slug,
        string $version,
        string $file,
        string $basename,
        string $dirPath,
        string $dirUrl,
        string $textDomain,
        string $optionPrefix
    ): self {
        return new self(
            $slug,
            $version,
            $file,
            $basename,
            $dirPath,
            $dirUrl,
            $textDomain,
            $optionPrefix
        );
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getBasename(): string
    {
        return $this->basename;
    }

    public function getDirPath(): string
    {
        return $this->dirPath;
    }

    public function getDirUrl(): string
    {
        return $this->dirUrl;
    }

    public function getTextDomain(): string
    {
        return $this->textDomain;
    }

    public function getOptionPrefix(): string
    {
        return $this->optionPrefix;
    }
}
