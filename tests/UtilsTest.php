<?php

/**
 * @author Jenner <hypxm@qq.com>
 * @license https://opensource.org/licenses/MIT MIT
 * @datetime: 2015/11/11 17:59
 */
class UtilsTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @return void
     * @doesNotPerformAssertions
     */
    public function testCheck()
    {
        $process = new UtilsTestProcess();
        \Jenner\SimpleFork\Utils::checkOverwriteRunMethod(get_class($process));
    }

    public function testRunMethodNotImplemented(): void
    {
        $this->expectException(RuntimeException::class);
        \Jenner\SimpleFork\Utils::checkOverwriteRunMethod(get_class(new UtilsTestProcessWithoutRunMethod()));
    }

    public function testProcessClassNotExtendingAnything(): void
    {
        $this->expectException(RuntimeException::class);
        \Jenner\SimpleFork\Utils::checkOverwriteRunMethod(stdClass::class);
    }

    public function testError(){
        $this->expectException(RuntimeException::class);
        \Jenner\SimpleFork\Utils::checkOverwriteRunMethod(get_class(new \Jenner\SimpleFork\Process()));
    }
}

class UtilsTestProcessWithoutRunMethod extends \Jenner\SimpleFork\Process
{

}

class UtilsTestProcess extends \Jenner\SimpleFork\Process
{
    public function run()
    {
        echo 'run' . PHP_EOL;
    }
}