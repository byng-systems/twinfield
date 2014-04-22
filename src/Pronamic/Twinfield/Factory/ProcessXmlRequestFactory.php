<?php
namespace Pronamic\Twinfield\Factory;

use Pronamic\Twinfield\Secure\Config;
use Pronamic\Twinfield\Secure\Login;
use Pronamic\Twinfield\Service\AbstractService;
use Pronamic\Twinfield\Service\ProcessXmlRequestService;
use Pronamic\Twinfield\Response\Response;
use Pronamic\Twinfield\Secure\SessionLoginHandler;

/**
 * All Factories used by all components extend this factory for common
 * shared methods that help normalize the usage between different components.\
 * 
 * @note this is a facade pattern. Named factory now, cant change it.
 * 
 * @author Leon Rowland <leon@rowland.nl>
 */
class ProcessXmlRequestFactory
{
    /**
     * Holds the secure config class
     * 
     * @var \Pronamic\Twinfield\Secure\Config
     */
    protected $config;

    /**
     * Holds the secure login class
     * 
     * @var \Pronamic\Twinfield\Secure\SessionLoginHandler
     */
    private $login;

    /**
     * Holds the response from a request.
     * 
     * @var \Pronamic\Twinfield\Response\Response
     */
    private $response;

    protected $processXmlRequestService;

    /**
     * Pass in the Secure\Config class and it will automatically
     * make the Secure\SessionLoginHandler for you.
     * 
     * @access public
     * @param \Pronamic\Twinfield\Secure\Config $config
     */
    public function __construct(Config $config)
    {
        $this->setConfig($config);
        $this->makeLogin();
    }
    // public function __construct(ProcessXmlRequestService $processXmlRequestService)
    // {
    //     $this->processXmlRequestService = $processXmlRequestService;
    // }

    /**
     * Sets the config class for usage in this factory
     * instance.
     * 
     * Returns the instance back.
     * 
     * @access public
     * @param \Pronamic\Twinfield\Secure\Config $config
     * @return \Pronamic\Twinfield\Factory\ParentFactory
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Returns this instances Secure\Config instance.
     * 
     * @access public
     * @return \Pronamic\Twinfield\Secure\Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Makes an instance of Secure\Login with the passed in 
     * Secure\Config instance.
     * 
     * @access public
     * @return boolean
     */
    public function makeLogin()
    {
        return $this->login = new SessionLoginHandler($this->getConfig());
    }

    /**
     * Returns this instances associated login instance.
     * 
     * @access public
     * @return \Pronamic\Twinfield\Secure\SessionLoginHandler
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Returns an new instance of Service with 
     * the already prepared Secure\SessionLoginHandler.
     * 
     * @access public
     * @return \Pronamic\Twinfield\Secure\Service
     */
    public function getService()
    {
        return new ProcessXmlRequestService($this->getLogin());
    }

    /**
     * Should be called by the child classes. Will set the response
     * document from an attempted SOAP request.
     * 
     * @access public
     * @param \Pronamic\Twinfield\Response\Response $response
     * @return \Pronamic\Twinfield\Factory\ParentFactory
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
        return $this;
    }

    /**
     * Returns the response that was last set.
     * 
     * @access public
     * @return \Pronamic\Twinfield\Response\Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
