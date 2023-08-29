<?php

/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2016/6/22
 * Time: 16:22
 */
class FileCacheTest extends \PHPUnit\Framework\TestCase
{

    public static function cacheDataProvider()
    {
        return array(
            array("/tmp/cache1", "key1", 1),
            array("/tmp/cache2", "key2", 2),
        );
    }
    
    public function testCacheExpiration()
    {
        $path = "/tmp/cache";
        $key = "expiring_soon";
        $value = "X";

        $cache = new \Jenner\SimpleFork\Cache\FileCache($path);

        $this->assertTrue($cache->set($key, $value, 2));
        $this->assertEquals($value, $cache->get($key));

        sleep(4);
        $this->assertNull($cache->get($key));
        $this->assertTrue($cache->set($key, $value));
        $this->assertTrue($cache->delete($key));
        $this->assertNull($cache->get($key));
        $this->assertTrue($cache->flush());
        $this->assertFileDoesNotExist($path);
    }

    /**
     * @dataProvider cacheDataProvider
     * @param $path
     * @param $key
     * @param $value
     */
    public function testCache($path, $key, $value) {
        $cache = new \Jenner\SimpleFork\Cache\FileCache($path);

        $this->assertFileExists($path);
        $this->assertTrue($cache->set($key, $value, 30));
        $this->assertTrue($cache->has($key));

        $this->assertEquals($value + 1, $cache->increment($key));
        $this->assertEquals($value, $cache->decrement($key));
    }

    public function testEmptyIncrementDecrement(): void
    {
        $cache = new \Jenner\SimpleFork\Cache\FileCache("/tmp/".tempnam("/tmp", "cache"));

        $this->assertEquals(100, $cache->increment("hundred", 100));
        $this->assertEquals(-100, $cache->decrement("minus_hundred", 100));
    }
}