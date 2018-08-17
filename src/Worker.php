<?php
/**
 * Channel worker
 * User: moyo
 * Date: 27/03/2018
 * Time: 2:58 PM
 */

namespace Carno\Channel;

use function Carno\Coroutine\async;
use Carno\Coroutine\Context;
use Closure;

class Worker
{
    /**
     * @var Chan
     */
    private $chan = null;

    /**
     * @var Closure
     */
    private $program = null;

    /**
     * @var Closure
     */
    private $processor = null;

    /**
     * @var Closure
     */
    private $done = null;

    /**
     * @var Closure
     */
    private $close = null;

    /**
     * @var bool
     */
    private $closing = false;

    /**
     * @var int
     */
    private $running = 0;

    /**
     * Worker constructor.
     * @param Chan $chan
     * @param Closure $program
     */
    public function __construct(Chan $chan, Closure $program)
    {
        $this->chan = $chan;
        $this->program = $program;

        $this->processor = function ($data, Context $ctx = null) {
            async($this->program, $ctx ?? new Context, $data)->then($this->done, $this->done);
        };

        $this->done = function () {
            $this->running --;
            $this->execute();
        };

        $this->close = function () {
            $this->closing = true;
        };

        $this->execute();
    }

    /**
     * @return int
     */
    public function activated() : int
    {
        return $this->running;
    }

    /**
     */
    private function execute() : void
    {
        for (;;) {
            if ($this->closing || $this->running >= $this->chan->cap()) {
                return;
            }

            $this->running ++;

            ($recv = $this->chan->recv())->then($this->processor, $this->close);

            if ($recv->pended()) {
                break;
            }
        }
    }
}
