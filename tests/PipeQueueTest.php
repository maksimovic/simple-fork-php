<?php

/**
 * @author Jenner <hypxm@qq.com>
 * @blog http://www.huyanping.cn
 * @license https://opensource.org/licenses/MIT MIT
 * @datetime: 2015/11/24 21:17
 */
class PipeQueueTest extends \PHPUnit\Framework\TestCase
{
    public function testPutAndGet()
    {
        $queue = new \Jenner\SimpleFork\Queue\PipeQueue();

        $this->assertTrue($queue->put('test'));

        $this->assertEquals('test', $queue->get());

		$test_string = str_repeat("A", "4000");
		$queue->put($test_string);
		$this->assertEquals($test_string, $queue->get(true));

		$queue->remove();
    }

	public function testValueTooLong()
	{
		$memory_limit = ini_get('memory_limit');
		ini_set('memory_limit', -1);

		$queue = new \Jenner\SimpleFork\Queue\PipeQueue();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("value is too long");

		$queue->put(str_repeat("1", (2 ** 31) + 1));

		ini_set('memory_limit', $memory_limit);
	}
}
