<?php

/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2015/10/23
 * Time: 16:58
 */
class PoolTest extends \PHPUnit\Framework\TestCase
{
    public function testMethods()
    {
        $process = new \Jenner\SimpleFork\Process(function () {
            sleep(1);
        }, 'test');
        $pool = new \Jenner\SimpleFork\Pool();
        $pool->execute($process, "My process");
        $this->assertEquals(1, $pool->aliveCount());
        $this->assertEquals($process, $pool->getProcessByPid($process->getPid()));
        $this->assertEquals($process, $pool->getProcessByName('My process'));
    }

    public function testAliveCountAndProcessRemoval()
    {
        $pool = new \Jenner\SimpleFork\Pool();
        for ($i = 0; $i < 10; $i++) {
            $process = new \Jenner\SimpleFork\Process(function () {
                sleep(2);
            });
            $pool->execute($process, "process{$i}");
        }
        $start = time();
        $this->assertEquals(10, $pool->aliveCount());
        $pool->wait();
        $time = time() - $start;
        $this->assertTrue($time >= 2);
        $this->assertEquals(0, $pool->aliveCount());

        $pool->removeProcessByName("process1");
        $pool->removeExitedProcess();
    }

    public function testShutdown()
    {
        $pool = new \Jenner\SimpleFork\Pool();
        for ($i = 0; $i < 10; $i++) {
            $process = new \Jenner\SimpleFork\Process(function () {
                sleep(2);
            });
            $pool->execute($process);
        }
        $start = time();
        $pool->shutdown();
        $time = time() - $start;
        $this->assertTrue($time < 2);
        $this->assertEquals(0, $pool->aliveCount());
    }

    public function testShutdownForce()
    {
        $pool = new \Jenner\SimpleFork\Pool();
        for ($i = 0; $i < 10; $i++) {
            $process = new \Jenner\SimpleFork\Process(function () {
                sleep(2);
            });
            $pool->execute($process);
        }
        $start = time();
        $pool->shutdownForce();
        $time = time() - $start;
        $this->assertTrue($time < 2);
        $this->assertEquals(0, $pool->aliveCount());
    }

    public function testPoolFactory(): void
    {
        $this->assertInstanceOf(\Jenner\SimpleFork\Pool::class, \Jenner\SimpleFork\PoolFactory::newPool());
        $this->assertInstanceOf(\Jenner\SimpleFork\SinglePool::class, \Jenner\SimpleFork\PoolFactory::newSinglePool());
    }

    public function testNoProcess(): void
    {
        $pool = new \Jenner\SimpleFork\Pool();
        $this->assertNull($pool->getProcessByPid("something"));
        $this->assertNull($pool->getProcessByName("something"));
    }
    
    public function testRemoveExitedProcessWhenNotFinished(): void
    {
        $pool = new \Jenner\SimpleFork\Pool();
        for ($i = 0; $i < 3; $i++) {
            $process = new \Jenner\SimpleFork\Process(function () {
                sleep(1);
            });
            $pool->execute($process, "process{$i}");
        }

        $this->assertEquals(3, $pool->aliveCount());

        $this->expectException(RuntimeException::class);
        $pool->removeProcessByName("process1");
    }
}