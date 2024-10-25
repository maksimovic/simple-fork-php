<?php
/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2015/8/12
 * Time: 20:52
 */

namespace Jenner\SimpleFork\Lock;

/**
 * sem lock
 *
 * @package Jenner\SimpleFork\Lock
 */
class Semaphore implements LockInterface
{
    private $lock_id;

    /**
     * @var bool
     */
    private $locked = false;

    /**
     * init a lock
     *
     * @param string $key
     * @param int $count
     * @throws \RuntimeException
     */
    private function __construct(string $key, int $count = 1)
    {
        if (($this->lock_id = sem_get($this->_stringToSemKey($key), $count)) === false) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException("Cannot create semaphore for key: {$key}");
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Semaphore requires a numeric value as the key
     *
     * @param $identifier
     * @return int
     */
    protected function _stringToSemKey($identifier)
    {
        $md5 = md5($identifier);
        $key = 0;
        for ($i = 0; $i < 32; $i++) {
            $key += ord($md5[$i]) * $i;
        }
        return $key;
    }

    /**
     * create a lock instance
     *
     * @param $key
     * @return Semaphore
     */
    public static function create($key)
    {
        return new Semaphore($key);
    }

    /**
     * release lock
     *
     * @throws \RuntimeException
     */
    public function __destruct()
    {
        if ($this->isLocked()) {
            $this->release();
        }
    }

    /**
     * is locked
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked === true ? true : false;
    }

    /**
     * release lock
     *
     * @return bool
     * @throws \RuntimeException
     */
    public function release(): bool
    {
        if (!$this->locked) {
            throw new \RuntimeException("release a non lock");
        }

        if (!sem_release($this->lock_id)) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }
        $this->locked = false;

        return true;
    }

    /**
     * get a lock
     *
     * @param bool $blocking
     * @return bool
     */
    public function acquire(bool $blocking = true): bool
    {
        if ($this->locked) {
            throw new \RuntimeException('already lock by yourself');
        }

        if ($blocking === false) {
            if (!sem_acquire($this->lock_id, true)) {
                return false;
            }
            $this->locked = true;

            return true;
        }

        if (!sem_acquire($this->lock_id)) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }
        $this->locked = true;

        return true;
    }

    /**
     * remove the semaphore resource
     *
     * @suppress PhanTypeMismatchArgumentInternalReal
     * @return bool
     */
    public function remove()
    {
        if ($this->locked) {
            throw new \RuntimeException('can not remove a locked semaphore resource');
        }
        if (!is_resource($this->lock_id)) {
            throw new \RuntimeException('can not remove a empty semaphore resource');
        }

        // @codeCoverageIgnoreStart
        // seems impossible to reproduce further below
        if (!sem_release($this->lock_id)) {
            return false;
        }

        return true;
        // @codeCoverageIgnoreEnd
    }
}