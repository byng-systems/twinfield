<?php
namespace Pronamic\Twinfield;

use Exception;
use Pronamic\Twinfield\Exception\SessionInvalidatedException;
use Pronamic\Twinfield\Exception\SessionTimeoutException;
use SoapClient as BaseSoapClient;
use SoapFault;

/**
 * Twinfield Soap Client.
 * 
 * @package Pronamic\Twinfield
 * @author Leon Rowland <leon@rowland.nl>
 * @copyright (c) 2013, Pronamic
 * @version 0.0.1
 */
class SoapClient extends BaseSoapClient
{

    /**
     * 
     */
    const SESSION_INVALIDATED_MESSAGE = 'Another user logged on with your user name. You are logged off now.';
    
    /**
     * 
     */
    const SESSION_TIMEOUT_MESSAGE = 'Your logon credentials are not valid anymore. Try to log on again.';
    
    
    
    /**
     * Overides the call method, to keep making
     * requests if it times out.
     * 
     * @todo require a better way than using exceptions.
     * 
     * @access public
     * @param string $functionName
     * @param mixed $arguments
     * @return SoapClient
     * @throws SoapFault
     */
    public function __call($functionName, $arguments)
    {
        try {
            return parent::__call($functionName, $arguments);
        } catch (SoapFault $ex) {
            throw $this->transformException($ex);
        }
    }
    
    /**
     * 
     * @param SoapFault $ex
     * @return Exception
     */
    protected function transformException(SoapFault $ex)
    {
        switch ($ex->getMessage()) {
            case self::SESSION_TIMEOUT_MESSAGE:
                return new SessionTimeoutException($ex);
            case self::SESSION_INVALIDATED_MESSAGE:
                return new SessionInvalidatedException($ex);
        }

        return $ex;
    }
}
