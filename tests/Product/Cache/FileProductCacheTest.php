<?php

declare(strict_types=1);

namespace App\Tests\Product\Cache;

use App\Product\Cache\FileProductCache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FileProductCacheTest extends TestCase
{
    private string $directory;

    private FileProductCache $cache;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/product-cache-test-'.bin2hex(random_bytes(8));
        $this->cache = new FileProductCache($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') ?: [] as $file) {
            unlink($file);
        }
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    #[Test]
    public function getReturnsNullWhenProductIsNotCached(): void
    {
        self::assertNull($this->cache->get('missing'));
    }

    #[Test]
    public function getReturnsWhatWasSet(): void
    {
        $data = ['id' => 'abc-123', 'name' => 'Škrtič kabelů', 'price' => 199.5, 'tags' => ['a', 'b']];

        $this->cache->set('abc-123', $data);

        self::assertSame($data, $this->cache->get('abc-123'));
    }

    #[Test]
    public function setOverwritesPreviousData(): void
    {
        $this->cache->set('abc-123', ['name' => 'Old']);
        $this->cache->set('abc-123', ['name' => 'New']);

        self::assertSame(['name' => 'New'], $this->cache->get('abc-123'));
    }

    #[Test]
    public function distinctIdsAreCachedSeparately(): void
    {
        $this->cache->set('first', ['name' => 'First']);
        $this->cache->set('second', ['name' => 'Second']);

        self::assertSame(['name' => 'First'], $this->cache->get('first'));
        self::assertSame(['name' => 'Second'], $this->cache->get('second'));
    }

    #[Test]
    public function idResemblingPathTraversalStaysInsideCacheDirectory(): void
    {
        $this->cache->set('../../escape', ['name' => 'Trapped']);

        self::assertSame(['name' => 'Trapped'], $this->cache->get('../../escape'));
        self::assertCount(1, glob($this->directory.'/*') ?: []);
        self::assertFileDoesNotExist(\dirname($this->directory).'/escape');
    }

    #[Test]
    #[DataProvider('corruptedContentProvider')]
    public function corruptedCacheFileIsTreatedAsMiss(string $content): void
    {
        $this->cache->set('abc-123', ['name' => 'Valid']);
        $files = glob($this->directory.'/*.json') ?: [];
        self::assertCount(1, $files);
        file_put_contents($files[0], $content);

        self::assertNull($this->cache->get('abc-123'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function corruptedContentProvider(): iterable
    {
        yield 'truncated json' => ['{"name": "Val'];
        yield 'json scalar' => ['"just a string"'];
        yield 'empty file' => [''];
    }
}