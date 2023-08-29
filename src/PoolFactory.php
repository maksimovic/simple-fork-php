<?php
/**
 * @author Jenner <hypxm@qq.com>
 * @blog http://www.huyanping.cn
 * @license https://opensource.org/licenses/MIT MIT
 * @datetime: 2015/11/19 21:14
 */

namespace Jenner\SimpleFork;

class PoolFactory
{
    /**
     * create a pool instance
     *
     * @return Pool
     */
    public static function newPool(): Pool
    {
        return new Pool();
    }

    /**
     * create a fixed pool instance
     *
     * @param int $max
     * @return FixedPool
     */
    public static function newFixedPool(int $max = 4): FixedPool
    {
        return new FixedPool($max);
    }

    /**
     * create a parallel pool instance
     *
     * @param callable|Runnable $callback
     * @param int $max
     * @return ParallelPool
     */
    public static function newParallelPool($callback, int $max = 4): ParallelPool
    {
        return new ParallelPool($callback, $max);
    }

    /**
     * create a single pool
     *
     * @return SinglePool
     */
    public static function newSinglePool(): SinglePool
    {
        return new SinglePool();
    }
}