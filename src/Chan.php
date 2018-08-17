<?php
/**
 * Channel API
 * User: moyo
 * Date: 22/09/2017
 * Time: 4:47 PM
 */

namespace Carno\Channel;

use Carno\Coroutine\Context;
use Carno\Promise\Promised;

interface Chan
{
    /**
     * @return int
     */
    public function cap() : int;

    /**
     * @param mixed $data
     * @param Context $ctx
     * @return Promised
     */
    public function send($data = null, Context $ctx = null) : Promised;

    /**
     * @return Promised
     */
    public function recv() : Promised;

    /**
     * @return void
     */
    public function close() : void;

    /**
     * @return Promised
     */
    public function closed() : Promised;
}
