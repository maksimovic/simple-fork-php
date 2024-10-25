<?php
/**
 * Created by PhpStorm.
 * User: Jenner
 * Date: 2015/8/12
 * Time: 15:15
 */

namespace Jenner\SimpleFork\Queue;


/**
 * system v message queue
 *
 * @package Jenner\SimpleFork\Queue
 */
class SystemVMessageQueue implements QueueInterface
{
    /**
     * @var int channel
     */
    protected $msg_type;

    /**
     * @var false|resource|\SysvMessageQueue
     */
    protected $queue;

    /**
     * @var bool
     */
    protected $serialize_needed;

    /**
     * @var bool
     */
    protected $block_send;

    /**
     * @var int
     */
    protected $option_receive;

    /**
     * @var int
     */
    protected $maxsize;

    /**
     * @var int
     */
    protected $key_t;

    /**
     * @var string
     */
    protected $ipc_filename;

    /**
     * @param string $ipc_filename ipc file to make ipc key.
     * if it does not exists, it will try to create the file.
     * @param int $channel message type
     * @param bool $serialize_needed serialize or not
     * @param bool $block_send if block when the queue is full
     * @param int $option_receive if the value is MSG_IPC_NOWAIT it will not
     * going to wait a message coming. if the value is null,
     * it will block and wait a message
     * @param int $maxsize the max size of queue
     */
    public function __construct(
        string $ipc_filename = __FILE__,
        int $channel = 1,
        bool $serialize_needed = true,
        bool $block_send = true,
        int $option_receive = MSG_IPC_NOWAIT,
        int $maxsize = 100000
    )
    {
        $this->ipc_filename = $ipc_filename;
        $this->msg_type = $channel;
        $this->serialize_needed = $serialize_needed;
        $this->block_send = $block_send;
        $this->option_receive = $option_receive;
        $this->maxsize = $maxsize;
        $this->initQueue($ipc_filename, $channel);
    }

    /**
     * init queue
     *
     * @param $ipc_filename
     * @param $msg_type
     * @throws \Exception
     */
    protected function initQueue($ipc_filename, $msg_type)
    {
        $this->key_t = $this->getIpcKey($ipc_filename, $msg_type);
        $this->queue = \msg_get_queue($this->key_t);
        if (!$this->queue) throw new \RuntimeException('msg_get_queue failed');
    }

    /**
     * @param $ipc_filename
     * @param $msg_type
     * @throws \Exception
     * @return int
     */
    public function getIpcKey($ipc_filename, $msg_type)
    {
        if (!file_exists($ipc_filename)) {
            $create_file = touch($ipc_filename);
            if ($create_file === false) {
                // @codeCoverageIgnoreStart
                throw new \RuntimeException('ipc_file is not exists and create failed');
                // @codeCoverageIgnoreEnd
            }
        }

        $key_t = \ftok($ipc_filename, $msg_type);
        if ($key_t == 0) throw new \RuntimeException('ftok error');

        return $key_t;
    }

    /**
     * get message
     *
     * @param bool $block if block when the queue is empty
     * @return mixed
     */
    public function get(bool $block = false)
    {
        $queue_status = $this->status();
        if ($queue_status['msg_qnum'] > 0) {
            $option_receive = $block ? 0 : $this->option_receive;
            if (\msg_receive(
                    $this->queue,
                    $this->msg_type,
                    $msgtype_erhalten,
                    $this->maxsize,
                    $data,
                    $this->serialize_needed,
                    $option_receive,
                    $err
                ) === true
            ) {
                return $data;
            }

            // @codeCoverageIgnoreStart
            throw new \RuntimeException($err);
            // @codeCoverageIgnoreEnd
        }

        return false;
    }

    public function status()
    {
        return \msg_stat_queue($this->queue);
    }

    /*
     * return array's keys
     * msg_perm.uid	 The uid of the owner of the queue.
     * msg_perm.gid	 The gid of the owner of the queue.
     * msg_perm.mode	 The file access mode of the queue.
     * msg_stime	 The time that the last message was sent to the queue.
     * msg_rtime	 The time that the last message was received from the queue.
     * msg_ctime	 The time that the queue was last changed.
     * msg_qnum	 The number of messages waiting to be read from the queue.
     * msg_qbytes	 The maximum number of bytes allowed in one message queue.
     *               On Linux, this value may be read and modified via /proc/sys/kernel/msgmnb.
     * msg_lspid	 The pid of the process that sent the last message to the queue.
     * msg_lrpid	 The pid of the process that received the last message from the queue.
     *
     * @return array
     */

    /**
     * put message
     *
     * @param $value
     * @return bool
     * @throws \Exception
     */
    public function put($value): bool
    {
        if (\msg_send($this->queue, $this->msg_type, $value, $this->serialize_needed, $this->block_send, $err)) {
            return true;
        }

        // @codeCoverageIgnoreStart
        throw new \RuntimeException($err);
        // @codeCoverageIgnoreEnd
    }

    /**
     * get the size of queue
     *
     * @return mixed
     */
    public function size()
    {
        $status = $this->status();

        return $status['msg_qnum'];
    }

    /**
     * allows you to change the values of the msg_perm.uid,
     * msg_perm.gid, msg_perm.mode and msg_qbytes fields of the underlying message queue data structure
     *
     * @param string $key status key
     * @param int $value status value
     * @return bool
     */
    public function setStatus(string $key, $value): bool
    {
        $this->checkSetPrivilege($key);

        if ($key === 'msg_qbytes') {
            // @codeCoverageIgnoreStart
            return $this->setMaxQueueSize($value);
            // @codeCoverageIgnoreEnd
        }

        $queue_status = [
            $key => $value
        ];

        return \msg_set_queue($this->queue, $queue_status);
    }

    /**
     * check the privilege of update the queue's status
     *
     * @param $key
     * @throws \Exception
     */
    private function checkSetPrivilege($key)
    {
        $privilege_field = array('msg_perm.uid', 'msg_perm.gid', 'msg_perm.mode');
        if (!\in_array($key, $privilege_field)) {
            $message = 'you can only change msg_perm.uid, msg_perm.gid, ' .
                ' msg_perm.mode and msg_qbytes. And msg_qbytes needs root privileges';

            throw new \RuntimeException($message);
        }
    }

    /**
     * update the max size of queue
     * need root
     *
     * @param $size
     * @throws \Exception
     * @return bool
     */
    public function setMaxQueueSize($size)
    {
        $user = \get_current_user();
        if ($user !== 'root')
            throw new \Exception('changing msg_qbytes needs root privileges');

        // @codeCoverageIgnoreStart
        return $this->setStatus('msg_qbytes', $size);
        // @codeCoverageIgnoreEnd
    }

    /**
     * remove queue
     *
     * @return bool
     */
    public function remove()
    {
        return \msg_remove_queue($this->queue);
    }

    /**
     * check if the queue is exists or not
     *
     * @param $key
     * @return bool
     */
    public function queueExists($key)
    {
        return \msg_queue_exists($key);
    }
}