<?php
/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2015/11/2
 * Time: 17:45
 */

namespace Jenner\SimpleFork;

/**
 * fixed pool
 *
 * @package Jenner\SimpleFork
 */
class FixedPool extends AbstractPool
{
    /**
     * @var int max process count
     */
    protected $max;

    public function __construct(int $max = 4)
    {
        $this->max = $max;
    }

    public function execute(Process $process): void
    {
        Utils::checkOverwriteRunMethod(get_class($process));

        if ($this->aliveCount() < $this->max && !$process->isStarted()) {
            $process->start();
        }

        $this->processes[] = $process;
    }

    /**
     * wait for all process done
     *
     * @param bool $block block the master process
     * to keep the sub process count all the time
     * @param int $sleep check time interval
     */
    public function wait(bool $block = false, $sleep = 100)
    {
        do {
            if ($this->isFinished()) {
                return;
            }
            parent::wait(false);
            if ($this->aliveCount() < $this->max) {
                foreach ($this->processes as $process) {
                    if ($process->isStarted()) continue;
                    $process->start();
                    if ($this->aliveCount() >= $this->max) break;
                }
            }
            $block ? usleep($sleep) : null;
        } while ($block);
    }

}