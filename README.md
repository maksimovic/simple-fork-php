SimpleFork
===================

This is a fork of `jenner/simple_fork` compatible with PHP 7.2/3/4/ and 8.0/1/2

[![codecov](https://codecov.io/github/maksimovic/simple-fork-php/graph/badge.svg?token=9kZkI3EaGv)](https://codecov.io/github/maksimovic/simple-fork-php)
![build status](https://github.com/maksimovic/simple-fork-php/actions/workflows/ci.yml/badge.svg)
 
Simple Fork Framework is based on PCNTL extension, the interfaces are like `Thread` and `Runnable` in Java.

Why SimpleFork
------------------------
Writing Multi-Processes programs are hard for freshman. You must consider that how to recover zombie processes, interprocess communication, especially handle the process signal.
SimpleFork framework provide several interfaces which like Java `Thread` and solutions in process pool, sync and IPC. You do not need to care about how to control multi-processes.

Require
---------------------
```bash
composer require maksimovic/simple-fork-php
```

Dependencies
----------------------
must  
+ php >= 7.2
+ ext-pcntl process control 

optional
+ ext-sysvmsg message queue
+ ext-sysvsem semaphore
+ ext-sysvshm shared memory
+ ext-redis redis cache and redis message queue

Property
---------------------------
+ Process Pool and Fixed Pool
+ Recover zombie process automatically
+ shared memory, system v message queue, semaphore lock, file lock, 
redis cache, redis queue
+ Three ways to make Process: extends Process, implements Runnable or 
create a process object with a callback function
+ You can get the status of sub process
+ You can stop any processes if you want, or just shutdown all processes
+ You can reload the processes by reload() method, then the processes 
will exit and start new processes instead.

Process Pool
----------------------------------
There are two pool you can use when you have more than one process or 
task to manage:Pool and FixedPool.
+ Pool: you can execute different processes in one Pool object. 
and call the `wait` method to wait for all the sub processes exiting
(or just do something else, but do not forget to call the `wait` method)
+ ParallelPool: it will keep the sub processes count, you should not init any
socket connection before the FixedPool start(share socket connection is dangerous
in multi processes).This class has a method `reload` which can reload 
all the sub processes. When you call `reload` method, the master will 
start new N processes and shutdown the old ones.
+ SinglePool: no matter how many processes you execute, it will always keep one
process starting and start another after it stopped.
+ FixedPool: no matter how many processes you execute, it will always keep N
processes starting and start another after it stopped. the active processes'
count is less then N+1 forever.

Notice
--------------------------
+ Remember that you should call the `Process::dispatchSignal` method to call
call signal handlers for pending signals.
+ It is not recommend that adding `declare(ticks=n);` at the start of program
to handle the pending signals.
+ A better way to handle the single is that calling `pcntl_signal_dispatch` 
instead of `declare` which is more is a waste of CPU resources
+ If the sub processes exit continually and quickly, you should set `n` to 
a small integer, else set a big one to save the CPU time.
+ If you want to register signal handler in the master process, the child 
will inherit the handler.
+ If you want to register signal handler in the child process before it start, 
you can call the `Process::registerSignalHandler` method. `start` 
method of the sub process is called, it will register the signal 
handler automatically.

Examples
------------------------- 
**A simple example.**  
```php
class TestRunnable implements \Jenner\SimpleFork\Runnable{

    /**
     * Entrance
     * @return mixed
     */
    public function run()
    {
        echo "I am a sub process" . PHP_EOL;
    }
}

$process = new \Jenner\SimpleFork\Process(new TestRunnable());
$process->start();
$process->wait();
```

**A process using callback**
```php
$process = new \Jenner\SimpleFork\Process(function(){
    for($i=0; $i<3; $i++){
        echo $i . PHP_EOL;
        sleep(1);
    }
});

$process->start();
$process->wait();
```

**Process communication using shared memory** 
```php
class Producer extends \Jenner\SimpleFork\Process{
    public function run(){
        $cache = new \Jenner\SimpleFork\Cache\SharedMemory();
        //$cache = new \Jenner\SimpleFork\Cache\RedisCache();
        for($i = 0; $i<10; $i++){
            $cache->set($i, $i);
            echo "set {$i} : {$i}" . PHH_EOL;
        }
    }
}

class Worker extends \Jenner\SimpleFork\Process{
    public function run(){
        sleep(5);
        $cache = new \Jenner\SimpleFork\Cache\SharedMemory();
        //$cache = new \Jenner\SimpleFork\Cache\RedisCache();
        for($i=0; $i<10; $i++){
            echo "get {$i} : " . $cache->get($i) . PHP_EOL;
        }
    }
}

$producer = new Producer();

$worker = new Worker();

$pool = new \Jenner\SimpleFork\Pool();
$pool->execute($producer);
$pool->execute($worker);
$pool->wait();
```

**Process communication using system v message queue** 
```php
class Producer extends \Jenner\SimpleFork\Process
{
    public function run()
    {
        $queue = new \Jenner\SimpleFork\Queue\SystemVMessageQueue();
        //$queue = new \Jenner\SimpleFork\Queue\RedisQueue();
        for ($i = 0; $i < 10; $i++) {
            echo getmypid() . PHP_EOL;
            $queue->put($i);
        }
    }
}

class Worker extends \Jenner\SimpleFork\Process
{
    public function run()
    {
        sleep(5);
        $queue = new \Jenner\SimpleFork\Queue\SystemVMessageQueue();
        //$queue = new \Jenner\SimpleFork\Queue\RedisQueue();
        for ($i = 0; $i < 10; $i++) {
            $res = $queue->get();
            echo getmypid() . ' = ' . $i . PHP_EOL;
            var_dump($res);
        }
    }
}

$producer = new Producer();

$worker = new Worker();

$pool = new \Jenner\SimpleFork\Pool();
$pool->execute($producer);
$pool->execute($worker);
$pool->wait();
```

**Process communication using Semaphore lock**
```php
class TestRunnable implements \Jenner\SimpleFork\Runnable
{

    /**
     * @var \Jenner\SimpleFork\Lock\LockInterface
     */
    protected $sem;

    public function __construct()
    {
        $this->sem = \Jenner\SimpleFork\Lock\Semaphore::create("test");
        //$this->sem = \Jenner\SimpleFork\Lock\FileLock::create("/tmp/test.lock");
    }

    /**
     * @return mixed
     */
    public function run()
    {
        for ($i = 0; $i < 20; $i++) {
            $this->sem->acquire();
            echo "my turn: {$i} " . getmypid() . PHP_EOL;
            $this->sem->release();
            sleep(1);
        }
    }
}

$pool = new \Jenner\SimpleFork\Pool();
$pool->execute(new \Jenner\SimpleFork\Process(new TestRunnable()));
$pool->execute(new \Jenner\SimpleFork\Process(new TestRunnable()));

$pool->wait();
```

**Process pool to manage processes**
```php
$pool = new \Jenner\SimpleFork\Pool();
$pool->execute(new \Jenner\SimpleFork\Process(new TestRunnable()));
$pool->execute(new \Jenner\SimpleFork\Process(new TestRunnable()));
$pool->execute(new \Jenner\SimpleFork\Process(new TestRunnable()));

$pool->wait();
```

**ParallelPool to manage processes**
```php
$fixed_pool = new \Jenner\SimpleFork\ParallelPool(new TestRunnable(), 10);
$fixed_pool->start();
$fixed_pool->keep(true);
```

**FixedPool to manage processes**
```php
$pool = new \Jenner\SimpleFork\FixedPool(2);
$pool->execute(new \Jenner\SimpleFork\Process(new TestRunnable()));
$pool->execute(new \Jenner\SimpleFork\Process(new TestRunnable()));
$pool->execute(new \Jenner\SimpleFork\Process(new TestRunnable()));

$pool->wait();
```

**SinglePool to manage processes**
```php
$pool = new \Jenner\SimpleFork\SinglePool();
$pool->execute(new \Jenner\SimpleFork\Process(new TestRunnable()));
$pool->execute(new \Jenner\SimpleFork\Process(new TestRunnable()));
$pool->execute(new \Jenner\SimpleFork\Process(new TestRunnable()));

$pool->wait();
```