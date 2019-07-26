<?php
/**
 * Channel piping
 * User: moyo
 * Date: 15/09/2017
 * Time: 2:24 PM
 */

namespace Carno\Channel;

use Carno\Channel\Exception\ChannelClosingException;
use Carno\Coroutine\Context;
use Carno\Promise\Promise;
use Carno\Promise\Promised;
use SplQueue;

class Channel implements Chan
{
    /**
     * @var int
     */
    private $cap = 1;

    /**
     * @var SplQueue
     */
    private $qData = null;

    /**
     * @var SplQueue
     */
    private $qSend = null;

    /**
     * @var SplQueue
     */
    private $qRecv = null;

    /**
     * @var bool
     */
    private $closing = false;

    /**
     * @var Promised
     */
    private $closed = null;

    /**
     * Channel constructor.
     * @param int $cap
     */
    public function __construct(int $cap = 1)
    {
        $this->cap = $cap;
        $this->qData = new SplQueue();
        $this->qSend = new SplQueue();
        $this->qRecv = new SplQueue();
    }

    /**
     * @return int
     */
    public function cap() : int
    {
        return $this->cap;
    }

    /**
     * @param mixed $data
     * @param Context $ctx
     * @return Promised
     */
    public function send($data = null, Context $ctx = null) : Promised
    {
        if ($this->closing) {
            throw new ChannelClosingException();
        }

        /**
         * @var Promised $wait
         */

        if ($this->qData->count() < $this->cap) {
            if ($this->qRecv->count() > 0) {
                $wait = $this->qRecv->dequeue();
                $wait->resolve($data, $ctx);
            } else {
                $this->qData->enqueue([$data, $ctx]);
            }

            return Promise::resolved();
        }

        $this->qData->enqueue([$data, $ctx]);
        $this->qSend->enqueue($block = Promise::deferred());

        return $block;
    }

    /**
     * @return Promised
     */
    public function recv() : Promised
    {
        if ($this->closing) {
            throw new ChannelClosingException();
        }

        /**
         * @var Promised $wait
         */

        if ($this->qData->count() > 0) {
            if ($this->qSend->count() > 0) {
                $wait = $this->qSend->dequeue();
                $wait->resolve();
            }

            return Promise::resolved(...$this->qData->dequeue());
        }

        $this->qRecv->enqueue($block = Promise::deferred());

        return $block;
    }

    /**
     * @return void
     */
    public function close() : void
    {
        if ($this->closing) {
            return;
        }

        $this->closing = true;

        /**
         * @var Promised $send
         * @var Promised $recv
         */

        while ($this->qSend->count() > 0) {
            $send = $this->qSend->dequeue();
            $send->throw(new ChannelClosingException());
        }

        while ($this->qRecv->count() > 0) {
            $recv = $this->qRecv->dequeue();
            $recv->throw(new ChannelClosingException());
        }

        $this->closed()->resolve();
    }

    /**
     * @return Promised
     */
    public function closed() : Promised
    {
        return $this->closed ?? $this->closed = Promise::deferred();
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return sprintf('%d|%d|%d', $this->qData->count(), $this->qSend->count(), $this->qRecv->count());
    }
}
