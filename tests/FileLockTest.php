<?php

/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2015/10/26
 * Time: 14:56
 */
class FileLockTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Jenner\SimpleFork\Lock\FileLock
     */
    protected $lock;

    public function setUp(): void
    {
        $this->lock = \Jenner\SimpleFork\Lock\FileLock::create(__FILE__);
    }

    public function tearDown(): void
    {
        unset($this->lock);
    }

    public function testLock()
    {
        $this->assertTrue($this->lock->acquire());
        $this->assertTrue($this->lock->isLocked());
        $this->assertTrue($this->lock->release());
        $this->assertFalse($this->lock->isLocked());
    }

    public function testAcquireException()
    {
        $this->expectException(RuntimeException::class);
        $this->lock->acquire();
        $this->lock->acquire();
    }

    public function testReleaseException()
    {
        $this->expectException(RuntimeException::class);
        $this->lock->release();
    }

    public function testCommunication()
    {
        $lock_file = "/tmp/".tempnam("/tmp", "simple-fork.lock");
        touch($lock_file);

        $process = new \Jenner\SimpleFork\Process(function () use ($lock_file) {
            $lock = \Jenner\SimpleFork\Lock\FileLock::create($lock_file);
            $lock->acquire(false);
            sleep(3);
            $lock->release();
        });

        $process->start();
        sleep(1);
        $lock = \Jenner\SimpleFork\Lock\FileLock::create($lock_file);
        $this->assertFalse($lock->acquire(false));
        $process->wait();
        $this->assertTrue($lock->acquire(false));
        $this->assertTrue($lock->release());
    }

    public function testLockNonExistingFile()
    {
        $this->expectException(RuntimeException::class);
        \Jenner\SimpleFork\Lock\FileLock::create("whatever");
    }
}