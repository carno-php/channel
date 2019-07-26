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
use Exception;
use PHPUnit\Framework\TestCase;
use Throwable;

class WorkerTest extends TestCase
{
    public function testRun1()
    {
        $sum = 0;

        $chan = new Channel(128);

        $worker = new Worker($chan, function (int $got) use (&$sum) {
            $sum += $got;
        });

        for ($i = 1; $i <= 100; $i++) {
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
            throw new Exception('test1');
        });

        for ($i = 0; $i < 100; $i++) {
            $chan->send($i);
        }

        $this->assertEquals(1, $worker->activated());
    }

    public function testFailure()
    {
        $chan = new Channel();

        $em = null;
        new Worker($chan, function (string $em) {
            throw new Exception($em);
        }, function (Throwable $e) use (&$em) {
            $em = $e->getMessage();
        });

        $chan->send('test1');
        $this->assertEquals('test1', $em);

        $chan->send('test2');
        $this->assertEquals('test2', $em);
    }
}
