<?php

declare(strict_types=1);

namespace App\Tests\Product\Counter;

use App\Product\Counter\FileProductRequestCounter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FileProductRequestCounterTest extends TestCase
{
    private string $directory;

    private string $filePath;

    private FileProductRequestCounter $counter;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/product-counter-test-'.bin2hex(random_bytes(8));
        $this->filePath = $this->directory.'/counts.txt';
        $this->counter = new FileProductRequestCounter($this->filePath);
    }

    protected function tearDown(): void
    {
        if (is_file($this->filePath)) {
            unlink($this->filePath);
        }
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    #[Test]
    public function createsFileAndCountsFirstRequest(): void
    {
        $this->counter->increment('abc-123');

        self::assertSame(['abc-123' => 1], $this->readCounts());
    }

    #[Test]
    public function countsRepeatedRequests(): void
    {
        $this->counter->increment('abc-123');
        $this->counter->increment('abc-123');
        $this->counter->increment('abc-123');

        self::assertSame(['abc-123' => 3], $this->readCounts());
    }

    #[Test]
    public function countsProductsIndependently(): void
    {
        $this->counter->increment('first');
        $this->counter->increment('second');
        $this->counter->increment('first');

        self::assertSame(['first' => 2, 'second' => 1], $this->readCounts());
    }

    #[Test]
    public function handlesIdsWithDelimiterCharacters(): void
    {
        $trickyId = "id with\ttab and\nnewline";

        $this->counter->increment($trickyId);
        $this->counter->increment($trickyId);

        self::assertSame([$trickyId => 2], $this->readCounts());
    }

    #[Test]
    public function numericIdDoesNotCorruptOtherCounts(): void
    {
        $this->counter->increment('abc-123');
        $this->counter->increment('123');
        $this->counter->increment('abc-123');

        self::assertSame(['abc-123' => 2, '123' => 1], $this->readCounts());
    }

    /**
     * Reads the plain-text file the way its consumers (marketing) would.
     *
     * @return array<array-key, int>
     */
    private function readCounts(): array
    {
        $counts = [];
        foreach (file($this->filePath, \FILE_IGNORE_NEW_LINES | \FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            [$encodedId, $count] = explode("\t", $line, 2);
            $counts[rawurldecode($encodedId)] = (int) $count;
        }

        return $counts;
    }
}