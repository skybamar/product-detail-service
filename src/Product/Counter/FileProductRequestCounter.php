<?php

declare(strict_types=1);

namespace App\Product\Counter;

final readonly class FileProductRequestCounter implements ProductRequestCounter
{
    public function __construct(
        private string $filePath,
    ) {
    }

    public function increment(string $id): void
    {
        $this->ensureDirectoryExists();

        $handle = fopen($this->filePath, 'c+');
        if ($handle === false) {
            throw new \RuntimeException(sprintf('Unable to open counter file "%s".', $this->filePath));
        }

        try {
            if (!flock($handle, \LOCK_EX)) {
                throw new \RuntimeException(sprintf('Unable to lock counter file "%s".', $this->filePath));
            }

            $content = stream_get_contents($handle);
            $counts = $this->parse($content === false ? '' : $content);
            $counts[$id] = ($counts[$id] ?? 0) + 1;
            $output = $this->format($counts);

            rewind($handle);
            ftruncate($handle, 0);
            if (fwrite($handle, $output) !== \strlen($output)) {
                throw new \RuntimeException(sprintf('Unable to write counter file "%s".', $this->filePath));
            }
            fflush($handle);
        } finally {
            fclose($handle);
        }
    }

    /**
     * PHP silently turns numeric-string array keys into ints, so the id keys
     * are typed array-key and normalized back to string when formatting.
     *
     * @return array<array-key, int>
     */
    private function parse(string $content): array
    {
        $counts = [];
        foreach (explode("\n", $content) as $line) {
            if ($line === '') {
                continue;
            }
            [$encodedId, $count] = explode("\t", $line, 2) + [1 => '0'];
            $counts[rawurldecode($encodedId)] = (int) $count;
        }

        return $counts;
    }

    /**
     * @param array<array-key, int> $counts
     */
    private function format(array $counts): string
    {
        $lines = '';
        foreach ($counts as $id => $count) {
            $lines .= rawurlencode((string) $id)."\t".$count."\n";
        }

        return $lines;
    }

    private function ensureDirectoryExists(): void
    {
        $directory = \dirname($this->filePath);
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(sprintf('Unable to create counter directory "%s".', $directory));
        }
    }
}
