<?php

declare(strict_types=1);

namespace App\Product\Cache;

final readonly class FileProductCache implements ProductCache
{
    public function __construct(
        private string $directory,
    ) {
    }

    public function get(string $id): ?array
    {
        $content = @file_get_contents($this->path($id));
        if ($content === false) {
            return null;
        }

        try {
            $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!\is_array($data)) {
            return null;
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    public function set(string $id, array $data): void
    {
        $this->ensureDirectoryExists();

        $json = json_encode($data, \JSON_THROW_ON_ERROR);

        $tmpPath = tempnam($this->directory, 'tmp_');
        if ($tmpPath === false) {
            throw new \RuntimeException(sprintf('Unable to create a temporary cache file in "%s".', $this->directory));
        }

        if (file_put_contents($tmpPath, $json) !== \strlen($json)) {
            @unlink($tmpPath);

            throw new \RuntimeException(sprintf('Unable to write cache file in "%s".', $this->directory));
        }

        if (!rename($tmpPath, $this->path($id))) {
            @unlink($tmpPath);

            throw new \RuntimeException(sprintf('Unable to move cache file into place in "%s".', $this->directory));
        }
    }

    private function path(string $id): string
    {
        return $this->directory.'/'.hash('sha256', $id).'.json';
    }

    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->directory) && !@mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new \RuntimeException(sprintf('Unable to create cache directory "%s".', $this->directory));
        }
    }
}