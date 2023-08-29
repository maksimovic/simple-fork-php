<?php
/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2015/8/12
 * Time: 15:11
 */

namespace Jenner\SimpleFork\Queue;


/**
 * queue for processes to transfer data
 *
 * @package Jenner\SimpleFork\Queue
 */
interface QueueInterface
{
    /**
     * put value into the queue of channel
     *
     * @param mixed $value
     * @return bool
     */
    public function put($value): bool;

    /**
     * get value from the queue of channel
     *
     * @param bool $block if block when the queue is empty
     * @return mixed
     */
    public function get(bool $block = false);
}