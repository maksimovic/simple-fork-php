<?php
/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2015/8/21
 * Time: 14:30
 */

namespace Jenner\SimpleFork\Lock;


/**
 * file lock
 *
 * @package Jenner\SimpleFork\Lock
 */
class FileLock implements LockInterface
{
    /**
     * @var string lock file
     */
    protected $file;

    /**
     * @var resource
     */
    protected $fp;

    /**
     * @var bool
     */
    protected $locked = false;

    private function __construct(string $file)
    {
        if (!file_exists($file) || !is_readable($file)) {
            throw new \RuntimeException("{$file} is not exists or not readable");
        }
        $this->fp = fopen($file, "r+");
        if (!is_resource($this->fp)) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException("open {$file} failed");
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * create a file lock instance
     * if the file is not exists, it will be created
     *
     * @param string $file lock file
     * @return FileLock
     */
    public static function create(string $file): FileLock
    {
        return new FileLock($file);
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

        if ($blocking) {
            $locked = flock($this->fp, LOCK_EX);
        } else {
            $locked = flock($this->fp, LOCK_EX | LOCK_NB);
        }

        if ($locked !== true) {
            return false;
        }
        $this->locked = true;

        return true;
    }

    /**
     * is locked
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked === true;
    }

    /**
     * @deprecated
     * @codeCoverageIgnore
     */
    public function __destory()
    {
        if ($this->locked) {
            $this->release();
        }
    }

    /**
     * release lock
     *
     * @return bool
     */
    public function release(): bool
    {
        if (!$this->locked) {
            throw new \RuntimeException('release a non lock');
        }

        $unlock = flock($this->fp, LOCK_UN);
        fclose($this->fp);
        if ($unlock !== true) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }
        $this->locked = false;

        return true;
    }
}