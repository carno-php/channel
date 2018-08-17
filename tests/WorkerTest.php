<?php
/**
 * Worker test
 * User: moyo
 * Date: 2018/7/18
 * Time: 4:12 PM
 */

namespace Carno\Channel\Tests;

use Carno\Channel\Channel;
use Carno\Channel\Worker;
use PHPUnit\Framework\TestCase;

class WorkerTest extends TestCase
{
    public function testRun1()
    {
        $sum = 0;

        $chan = new Channel(128);

        $worker = new Worker($chan, function (int $got) use (&$sum) {
            $sum += $got;
        });

        for ($i = 1; $i <= 100; $i ++) {
            $chan->send($i);
        }

        $this->assertEquals(1, $worker->activated());
        $this->assertEquals(5050, $sum);

        $chan->close();

        unset($chan, $worker);
    }

    public function testRun2()
    {
        $chan = new Channel(32);

        $worker = new Worker($chan, function () {
            throw new \Exception('test');
        });

        for ($i = 0; $i < 100; $i ++) {
            $chan->send($i);
        }

        $this->assertEquals(1, $worker->activated());
    }
}
