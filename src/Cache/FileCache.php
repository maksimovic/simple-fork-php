<?php
/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2016/6/22
 * Time: 16:18
 */

namespace Jenner\SimpleFork\Cache;

class FileCache implements CacheInterface
{
    /**
     * @var string
     */
    private $cache_dir;

    public function __construct(string $cache_dir)
    {
        $this->cache_dir = $cache_dir;

        if (!is_dir($cache_dir)) {
            $make_dir_result = mkdir($cache_dir, 0755, true);
            if ($make_dir_result === false) throw new \Exception('Cannot create the cache directory');
        }
    }


    /**
     * get value by key, and check if it is expired
     *
     * @param string $key
     * @param string $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $cache_data = $this->getItem($key);
        if ($cache_data === false || !is_array($cache_data)) return $default;

        return $cache_data['data'];
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $expire
     * @return mixed
     */
    public function set(string $key, $value, int $expire = 0)
    {
        return $this->setItem($key, $value, time(), $expire);
    }

    /**
     * @param $key
     * @param $value
     * @param $time
     * @param $expire
     * @return bool
     */
    private function setItem($key, $value, $time, int $expire)
    {
        $cache_file = $this->createCacheFile($key);
        if ($cache_file === false) return false;

        $cache_data = array('data' => $value, 'time' => $time, 'expire' => $expire);
        $cache_data = serialize($cache_data);

        $put_result = file_put_contents($cache_file, $cache_data);
        if ($put_result === false) return false;

        return true;
    }

    /**
     * @param $key
     * @return bool|string
     */
    private function createCacheFile($key)
    {
        $cache_file = $this->path($key);
        if (!file_exists($cache_file)) {
            $directory = dirname($cache_file);
            if (!is_dir($directory)) {
                $make_dir_result = mkdir($directory, 0755, true);
                if ($make_dir_result === false) return false;
            }
            $create_result = touch($cache_file);
            if ($create_result === false) return false;
        }

        return $cache_file;
    }

    /**
     * @param $key
     * @return bool
     */
    public function has($key): bool
    {
        $value = $this->get($key);
        if ($value === false) return false;

        return true;
    }

    /**
     * @suppress PhanTypeMismatchDimAssignment
     *
     * @param $key
     * @param int $value
     * @return mixed
     */
    public function increment($key, int $value = 1)
    {
        $item = $this->getItem($key);
        if ($item === false) {
            $set_result = $this->set($key, $value);
            if ($set_result === false) return false;
            return $value;
        }

        $check_expire = $this->checkExpire($item);
        if ($check_expire === false) return false;

        $item['data'] += $value;

        $result = $this->setItem($key, $item['data'], $item['time'], $item['expire']);
        if ($result === false) return false;

        return $item['data'];
    }

    /**
     * @suppress PhanTypeMismatchDimAssignment
     * @param $key
     * @param int $value
     * @return mixed
     */
    public function decrement($key, $value = 1)
    {
        $item = $this->getItem($key);
        if ($item === false) {
            $value = 0 - $value;
            $set_result = $this->set($key, $value);
            if ($set_result === false) return false;
            return $value;
        }

        $check_expire = $this->checkExpire($item);
        if ($check_expire === false) return false;

        $item['data'] -= $value;

        $result = $this->setItem($key, $item['data'], $item['time'], $item['expire']);
        if ($result === false) return false;

        return $item['data'];
    }

    /**
     * @param string $key
     * @return boolean
     */
    public function delete(string $key): bool
    {
        $cache_file = $this->path($key);
        if (file_exists($cache_file)) {
            $unlink_result = unlink($cache_file);
            if ($unlink_result === false) return false;
        }

        return true;
    }

    public function flush(): bool
    {
        return $this->delTree($this->cache_dir);
    }

    /**
     * @param string $dir
     * @return bool
     */
    function delTree(string $dir): bool
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    /**
     * @param string $key
     * @return string
     */
    protected function path(string $key): string
    {
        $parts = array_slice(str_split($hash = md5($key), 2), 0, 2);
        return $this->cache_dir . '/' . implode('/', $parts) . '/' . $hash;
    }

    /**
     * @param $key
     * @return bool|mixed|string
     */
    protected function getItem($key)
    {
        $cache_file = $this->path($key);
        if (!file_exists($cache_file) || !is_readable($cache_file)) {
            return false;
        }

        $data = file_get_contents($cache_file);

        if (empty($data)) return false;

        $cache_data = unserialize($data);

        if ($cache_data === false) {
            // @codeCoverageIgnoreStart
            return false;
            // @codeCoverageIgnoreEnd
        }

        $check_expire = $this->checkExpire($cache_data);
        if ($check_expire === false) {
            $this->delete($key);
            return false;
        }

        return $cache_data;
    }

    /**
     * @param array $cache_data
     * @return bool
     */
    protected function checkExpire(array $cache_data): bool
    {
        $time = time();

        $is_expired = (int) $cache_data['expire'] !== 0 && ((int) $cache_data['time'] + (int) $cache_data['expire'] < $time);

        return !$is_expired;
    }
}