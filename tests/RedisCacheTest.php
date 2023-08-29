<?php

/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2015/10/26
 * Time: 14:50
 */
class RedisCacheTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Jenner\SimpleFork\Cache\RedisCache
     */
    protected $cache;

    public function setUp(): void
    {
        if(!extension_loaded("Redis")){
            $this->markTestSkipped("Redis extension is not loaded");
        }
    }

    public function testAll(): void
    {
        $this->cache = new Jenner\SimpleFork\Cache\RedisCache();
        $this->cache->set('cache', 'test');

        $this->assertTrue($this->cache->has('cache'));
        $this->assertEquals('test', $this->cache->get('cache'));
        $this->assertTrue($this->cache->delete('cache'));
        $this->assertFalse($this->cache->delete('thiskeydoesntexist'));
        $this->assertNull($this->cache->get('cache'));

        $this->cache->close();
        unset($this->cache);
    }
    
    public function testCantConnect()
    {
        $this->expectException(RedisException::class);
        new Jenner\SimpleFork\Cache\RedisCache('127.0.0.1', 1234);
    }

    public function testCantSelectDatabase(): void
    {
        $this->expectException(RuntimeException::class);
        new \Jenner\SimpleFork\Cache\RedisCache('127.0.0.1', 6379, -1);
    }

    public function testNoPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new \Jenner\SimpleFork\Cache\RedisCache('127.0.0.1', 6379, 0, '');
    }

}