<?php
namespace Pronamic\Twinfield\Factory;

use Pronamic\Twinfield\Secure\SessionLoginHandler;
use Pronamic\Twinfield\Service\ProcessXmlRequestService;
use Pronamic\Twinfield\Customer\CustomerFactory;
use Pronamic\Twinfield\Browse\BrowseFactory;
use Pronamic\Twinfield\Transaction\TransactionFactory;


class ProcessXmlServiceFactory
{
    /**
     * @var \Pronamic\Twinfield\Secure\SessionLoginHandler
     */
    protected $sessionLoginHandler;

    /**
     *
     * @var \Pronamic\Twinfield\Service\ProcessXmlRequestService
     */
    protected $processXmlRequestService;
    
    /**
     * Pass in the Secure\Config class and it will automatically
     * make the Secure\SessionLoginHandler for you.
     * 
     * @access public
     * @param \Pronamic\Twinfield\Secure\Config $config
     */
    public function __construct(SessionLoginHandler $sessionLoginHandler)
    {
        $this->sessionLoginHandler = $sessionLoginHandler;
        $this->processXmlRequestService = new ProcessXmlRequestService($sessionLoginHandler);
    }

    /**
     * [buildCustomerFactory description]
     * 
     * @param \Pronamic\Twinfield\Secure\ConfigInterface $config
     * 
     * @return \Pronamic\Twinfield\Customer\CustomerFactory
     */
    public function buildCustomerFactory()
    {
        return new CustomerFactory($this->processXmlRequestService);
    }
    
    /**
     * [buildCustomerFactory description]
     * 
     * @param \Pronamic\Twinfield\Secure\ConfigInterface $config
     * 
     * @return \Pronamic\Twinfield\Browse\BrowseFactory
     */
    public function buildBrowseFactory()
    {
        return new BrowseFactory($this->processXmlRequestService);
    }
    
    /**
     * [buildTransactionFactory description]
     * 
     * @return \Pronamic\Twinfield\Transaction\TransactionFactory
     */
    public function buildTransactionFactory()
    {
        return new TransactionFactory($this->processXmlRequestService);
    }
}