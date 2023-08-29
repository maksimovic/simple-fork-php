<?php
/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2015/11/3
 * Time: 14:37
 */

namespace Jenner\SimpleFork;

/**
 * processes' pool
 *
 * @package Jenner\SimpleFork
 */
abstract class AbstractPool
{
    /**
     * process list
     *
     * @var Process[]
     */
    protected $processes = [];

    /**
     * get process by pid
     *
     * @param $pid
     * @return null|Process
     */
    public function getProcessByPid($pid): ?Process
    {
        foreach ($this->processes as $process) {
            if ($process->getPid() == $pid) {
                return $process;
            }
        }

        return null;
    }

    /**
     * shutdown sub process and no wait. it is dangerous,
     * maybe the sub process is working.
     */
    public function shutdownForce(): void
    {
        $this->shutdown(SIGKILL);
    }

    /**
     * shutdown all process
     *
     * @param int $signal
     */
    public function shutdown(int $signal = SIGTERM): void
    {
        foreach ($this->processes as $process) {
            if ($process->isRunning()) {
                $process->shutdown(true, $signal);
            }
        }
    }

    /**
     * if all processes are stopped
     *
     * @return bool
     */
    public function isFinished(): bool
    {
        foreach ($this->processes as $process) {
            if (!$process->isStopped()) {
                return false;
            }
        }
        return true;
    }

    /**
     * waiting for the sub processes to exit
     *
     * @param bool|true $block if true the parent process will be blocked until all
     * sub processes exit. else it will check if there are processes that had been exited once and return.
     * @param int $sleep when $block is true, it will check sub processes every $sleep minute
     */
    public function wait(bool $block = true, int $sleep = 100)
    {
        do {
            foreach ($this->processes as $process) {
                if (!$process->isRunning()) {
                    continue;
                }
            }
            usleep($sleep);
        } while ($block && $this->aliveCount() > 0);
    }

    /**
     * get the count of running processes
     *
     * @return int
     */
    public function aliveCount(): int
    {
        $count = 0;
        foreach ($this->processes as $process) {
            if ($process->isRunning()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * get process by name
     *
     * @param string $name process name
     * @return Process|null
     */
    public function getProcessByName(string $name): ?Process
    {
        foreach ($this->processes as $process) {
            if ($process->name() === $name) {
                return $process;
            }
        }

        return null;
    }

    /**
     * remove process by name
     *
     * @param string $name $name
     * @throws \RuntimeException
     */
    public function removeProcessByName(string $name): void
    {
        foreach ($this->processes as $key => $process) {
            if ($process->name() === $name) {
                if ($process->isRunning()) {
                    throw new \RuntimeException("can not remove a running process");
                }
                unset($this->processes[$key]);
            }
        }
    }

    /**
     * remove exited process
     */
    public function removeExitedProcess(): void
    {
        foreach ($this->processes as $key => $process) {
            if ($process->isStopped()) {
                unset($this->processes[$key]);
            }
        }
    }

    /**
     * return process count
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->processes);
    }

    /**
     * get all processes
     *
     * @return Process[]
     */
    public function getProcesses(): array
    {
        return $this->processes;
    }
}