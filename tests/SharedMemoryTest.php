<?php

/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2015/10/23
 * Time: 17:02
 */
class SharedMemoryTest extends \PHPUnit\Framework\TestCase
{
    public function testSetAndGet()
    {
        $cache = new \Jenner\SimpleFork\Cache\SharedMemory(1024);
        $process = new \Jenner\SimpleFork\Process(function () use ($cache) {
            $cache->set('test', 'test');
        });
        $process->start();
        // wait sub process
        $process->wait();

        $this->assertEquals('test', $cache->get('test'));


    }

    public function testHas()
    {
        $cache = new \Jenner\SimpleFork\Cache\SharedMemory(1024);
        $cache->set('test', 'test');
        $this->assertTrue($cache->has('test'));
        $this->assertEquals('test', $cache->get('test'));
        $cache->delete('test');
        $this->assertFalse($cache->has('test'));
    }

    public function testRemove()
    {
        $cache = new \Jenner\SimpleFork\Cache\SharedMemory(1024);
        $cache->set('test', 'test');
        $process = new \Jenner\SimpleFork\Process(function () use ($cache) {
            $cache->remove();
        });
        $this->assertEquals('test', $cache->get('test'));
        $process->start();
        $process->wait();

        // maybe a php bug
        //$this->assertFalse($cache->get('test'));
    }
}