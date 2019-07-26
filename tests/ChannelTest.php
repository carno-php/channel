<?php
/**
 * Channel test
 * User: moyo
 * Date: 26/03/2018
 * Time: 10:56 PM
 */

namespace Carno\Channel\Tests;

use Carno\Channel\Channel;
use PHPUnit\Framework\TestCase;

class ChannelTest extends TestCase
{
    public function testSR1()
    {
        $chain = new Channel();

        $cs = 0;

        $chain->recv()->then(function () use (&$cs) {
            $cs++;
        });

        $this->assertEquals('0|0|1', (string)$chain);

        $chain->recv()->then(function () use (&$cs) {
            $cs++;
        });

        $this->assertEquals('0|0|2', (string)$chain);

        $this->assertEquals(0, $cs);

        $this->assertFalse($chain->send()->pended());

        $this->assertEquals('0|0|1', (string)$chain);

        $this->assertEquals(1, $cs);

        $this->assertFalse($chain->send()->pended());

        $this->assertEquals('0|0|0', (string)$chain);

        $this->assertEquals(2, $cs);

        $this->assertFalse($chain->send()->pended());

        $this->assertEquals('1|0|0', (string)$chain);

        $this->assertTrue(($s4 = $chain->send())->pended());

        $this->assertEquals('2|1|0', (string)$chain);

        $this->assertEquals(2, $cs);

        $chain->recv()->then(function () use (&$cs) {
            $cs++;
        });

        $this->assertEquals('1|0|0', (string)$chain);

        $this->assertEquals(3, $cs);

        $this->assertFalse($s4->pended());
    }

    public function testSR2()
    {
        $chan = new Channel(1024);

        for ($i = 0; $i < 2048; $i++) {
            $chan->send($i);
        }

        $this->assertEquals('2048|1024|0', (string)$chan);

        $r = 0;

        for ($j = 0; $j < 2048 + 512; $j++) {
            $chan->recv()->then(function () use (&$r) {
                $r++;
            });
        }

        $this->assertEquals(2048, $r);

        $this->assertEquals('0|0|512', (string)$chan);

        for ($k = 0; $k < 512; $k++) {
            $chan->send($k);
        }

        $this->assertEquals(2048 + 512, $r);

        $this->assertEquals('0|0|0', (string)$chan);
    }

    public function testSR3()
    {
        $chan = new Channel();

        $r = 0;

        for ($i = 0; $i < 1024; $i++) {
            $chan->recv()->then(function () use (&$r) {
                $r++;
            });
        }

        $this->assertEquals(0, $r);

        $this->assertEquals('0|0|1024', (string)$chan);

        for ($j = 0; $j < 512; $j++) {
            $chan->send($j);
        }

        $this->assertEquals(512, $r);

        $this->assertEquals('0|0|512', (string)$chan);

        for ($k = 0; $k < 512; $k++) {
            $chan->send($k);
        }

        $this->assertEquals(1024, $r);

        $this->assertEquals('0|0|0', (string)$chan);
    }
}
