<?php

namespace Pronamic\Twinfield\Service;

use Pronamic\Twinfield\Secure\SessionLoginHandler;

class AbstractService
{
    /**
     * @var \Pronamic\Twinfield\Secure\SessionLoginHandler
     */
    protected $loginHandler;

    /**
     * [__construct description]
     * 
     * @param SessionLoginHandler $loginHandler
     */
    public function __construct(SessionLoginHandler $loginHandler)
    {
        $this->loginHandler = $loginHandler;
    }
}