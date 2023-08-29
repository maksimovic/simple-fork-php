<?php

/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2015/10/26
 * Time: 17:24
 */
class SystemVMessageQueueTest extends \PHPUnit\Framework\TestCase
{
    public function testCommunication()
    {
        $process = new \Jenner\SimpleFork\Process(function () {
            $queue = new \Jenner\SimpleFork\Queue\SystemVMessageQueue();
            $queue->put('test');
        });
        $process->start();
        $process->wait();

        $queue = new \Jenner\SimpleFork\Queue\SystemVMessageQueue();
        $this->assertEquals(1, $queue->size());
        $this->assertEquals('test', $queue->get());

        $this->assertFalse($queue->queueExists(1));
    }

    /**
     * @doesNotPerformAssertions
     * @return void
     */
    public function testInitWithNewFile()
    {
        new \Jenner\SimpleFork\Queue\SystemVMessageQueue("/tmp".tempnam("/tmp", ""));
    }
    
    public function testNothingInQueue()
    {
        $queue = new \Jenner\SimpleFork\Queue\SystemVMessageQueue();

        $this->assertFalse($queue->get());
    }
    
    public function testPut()
    {
        $queue = new \Jenner\SimpleFork\Queue\SystemVMessageQueue();

        $this->assertTrue($queue->put("test"));
    }
    
    public function testSetMsgBytesWithoutRoot()
    {
        $queue = new \Jenner\SimpleFork\Queue\SystemVMessageQueue();

        $this->expectException(RuntimeException::class);
        $queue->setStatus('msg_qbytes', 5);
    }
    
    public function testSetMaxQueueSizeWithoutRoot()
    {
        $queue = new \Jenner\SimpleFork\Queue\SystemVMessageQueue();

        $this->expectException(Exception::class);
        $queue->setMaxQueueSize(5);
    }
    
    public function testSetStatus(): void
    {
        $queue = new \Jenner\SimpleFork\Queue\SystemVMessageQueue();
        $this->assertTrue($queue->setStatus('msg_perm.mode', 1));
    }

    public function tearDown(): void
    {
        (new \Jenner\SimpleFork\Queue\SystemVMessageQueue())->remove();
    }

}