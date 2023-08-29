<?php
/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2015/8/21
 * Time: 14:24
 */

namespace Jenner\SimpleFork\Lock;

interface LockInterface
{
    public function acquire(bool $blocking = true): bool;

    public function release(): bool;

    public function isLocked(): bool;
}