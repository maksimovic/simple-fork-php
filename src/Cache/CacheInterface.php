<?php
/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2015/8/12
 * Time: 14:59
 */

namespace Jenner\SimpleFork\Cache;

/**
 * cache for processes shared variables
 *
 * @package Jenner\SimpleFork\Cache
 */
interface CacheInterface
{
    /**
     * get var
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * set var
     *
     * @param string $key
     * @param null $value
     * @return mixed
     */
    public function set(string $key, $value);

    /**
     * has var ?
     *
     * @param $key
     * @return bool
     */
    public function has($key): bool;

    /**
     * delete var
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;

}