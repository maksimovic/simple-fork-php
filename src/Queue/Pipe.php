<?php
/**
 * @author Jenner <hypxm@qq.com>
 * @blog http://www.huyanping.cn
 * @license https://opensource.org/licenses/MIT MIT
 * @datetime: 2015/11/24 16:29
 */

namespace Jenner\SimpleFork\Queue;

class Pipe
{
    /**
     * @var resource
     */
    protected $read;

    /**
     * @var resource
     */
    protected $write;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var bool
     */
    protected $block;

    /**
     * @param string $filename fifo filename
     * @param int $mode
     * @param bool $block if blocking
     */
    public function __construct(string $filename = '/tmp/simple-fork.pipe', int $mode = 0666, bool $block = false)
    {
        if (!file_exists($filename) && !posix_mkfifo($filename, $mode)) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException('create pipe failed');
            // @codeCoverageIgnoreEnd
        }
        if (filetype($filename) !== 'fifo') {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException('file exists and it is not a fifo file');
            // @codeCoverageIgnoreEnd
        }

        $this->filename = $filename;
        $this->block = $block;
    }

    public function setBlock(bool $block = true)
    {
        if (is_resource($this->read)) {
            $set = stream_set_blocking($this->read, $block);
            if (!$set) {
                // @codeCoverageIgnoreStart
                throw new \RuntimeException('stream_set_blocking failed');
                // @codeCoverageIgnoreEnd
            }
        }

        if (is_resource($this->write)) {
            $set = stream_set_blocking($this->write, $block);
            if (!$set) {
                // @codeCoverageIgnoreStart
                throw new \RuntimeException('stream_set_blocking failed');
                // @codeCoverageIgnoreEnd
            }
        }

        $this->block = $block;
    }

    /**
     * if the stream is blocking, you would better set the value of size,
     * it will not return until the data size is equal to the value of param size
     *
     * @param int $size
     * @return string|false
     */
    public function read(int $size = 1024)
    {
        if (!is_resource($this->read)) {
            $this->read = fopen($this->filename, 'r+');
            if (!is_resource($this->read)) {
                // @codeCoverageIgnoreStart
                throw new \RuntimeException('open file failed');
                // @codeCoverageIgnoreEnd
            }
            if (!$this->block) {
                $set = stream_set_blocking($this->read, false);
                if (!$set) {
                    // @codeCoverageIgnoreStart
                    throw new \RuntimeException('stream_set_blocking failed');
                    // @codeCoverageIgnoreEnd
                }
            }
        }

        return fread($this->read, $size);
    }

    /**
     * @param string $message
     * @return false|int
     */
    public function write(string $message)
    {
        if (!is_resource($this->write)) {
            $this->write = fopen($this->filename, 'w+');
            if (!is_resource($this->write)) {
                // @codeCoverageIgnoreStart
                throw new \RuntimeException('open file failed');
                // @codeCoverageIgnoreEnd
            }
            if (!$this->block) {
                $set = stream_set_blocking($this->write, false);
                if (!$set) {
                    // @codeCoverageIgnoreStart
                    throw new \RuntimeException('stream_set_blocking failed');
                    // @codeCoverageIgnoreEnd
                }
            }
        }

        return fwrite($this->write, $message);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close(): void
    {
        if (is_resource($this->read)) {
            fclose($this->read);
        }
        if (is_resource($this->write)) {
            fclose($this->write);
        }
    }

    public function remove(): bool
    {
        return unlink($this->filename);
    }
}
