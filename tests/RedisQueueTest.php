<?php

/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2015/10/26
 * Time: 17:18
 */
class RedisQueueTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Jenner\SimpleFork\Queue\RedisQueue
     */
    protected $queue;

    public function testAll()
    {
        if(!extension_loaded("Redis")){
            $this->markTestSkipped("Redis extension is not loaded");
        }
        $this->queue = new \Jenner\SimpleFork\Queue\RedisQueue();
        $this->assertTrue($this->queue->put('test'));
        $this->assertEquals('test', $this->queue->get());
        $this->assertEquals(0, $this->queue->size());
        $this->queue->close();
    }

    public function testCommunication()
    {
        if(!extension_loaded("Redis")){
            $this->markTestSkipped("Redis extension is not loaded");
        }
        $process = new \Jenner\SimpleFork\Process(function () {
            $queue = new \Jenner\SimpleFork\Queue\RedisQueue();
            $queue->put('test');
            $queue->close();
        });
        $process->start();
        $process->wait();
        $queue = new \Jenner\SimpleFork\Queue\RedisQueue();
        $this->assertEquals(1, $queue->size());
        $this->assertEquals('test', $queue->get());
        $queue->close();
    }

    public function testRemove()
    {
        $queue = new \Jenner\SimpleFork\Queue\RedisQueue();
        $queue->put("test");
        $this->assertEquals(1, $queue->remove());
        $queue->close();
    }

    public function testCantConnect()
    {
        $this->expectException(RedisException::class);
        new \Jenner\SimpleFork\Queue\RedisQueue('127.0.0.1', 1234);
    }

    public function testCantSelectDatabase(): void
    {
        $this->expectException(RuntimeException::class);
        new \Jenner\SimpleFork\Queue\RedisQueue('127.0.0.1', 6379, -1);
    }

    public function testEmptyChannel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new \Jenner\SimpleFork\Queue\RedisQueue('127.0.0.1', 6379, 0, '');
    }

    public function tearDown(): void
    {
        (new \Jenner\SimpleFork\Queue\RedisQueue())->remove();
    }

}