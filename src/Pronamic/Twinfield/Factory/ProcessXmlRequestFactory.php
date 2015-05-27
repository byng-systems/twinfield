<?php
namespace Pronamic\Twinfield\Factory;

use Pronamic\Twinfield\Exception\SessionException;
use Pronamic\Twinfield\Response\Response;
use Pronamic\Twinfield\Secure\SessionLoginHandler;
use Pronamic\Twinfield\Service\ProcessXmlRequestService;

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
     * Holds the response from a request.
     * 
     * @var Response
     */
    private $response;

    /**
     * @var ProcessXmlRequestService
     */
    protected $processXmlRequestService;

    /**
     * Constructor
     * 
     * @param ProcessXmlRequestService $processXmlRequestService
     */
    public function __construct(ProcessXmlRequestService $processXmlRequestService)
    {
        $this->processXmlRequestService = $processXmlRequestService;
    }

    /**
     * [getProcessXmlRequestService description]
     * 
     * @return ProcessXmlRequestService
     */
    public function getProcessXmlRequestService()
    {
        return $this->processXmlRequestService;
    }

    /**
     * [getSessionLoginHandler description]
     * 
     * @return SessionLoginHandler
     */
    public function getSessionLoginHandler()
    {
        return $this->getProcessXmlRequestService()->getSessionLoginHandler();
    }

    /**
     * 
     * @param SessionLoginHandler $sessionLoginHandler
     * @return ProcessXmlRequestFactory
     */
    public function setSessionLoginHandler(SessionLoginHandler $sessionLoginHandler)
    {
        $this->getProcessXmlRequestService()->setSessionLoginHandler($sessionLoginHandler);
        
        return $this;
    }

    /**
     * [execute description]
     * 
     * @param  [type] $request [description]
     * 
     * @return Response
     */
    public function execute($request)
    {
        if ($this->getSessionLoginHandler()->process()) {
            $service = $this->getService();
            
            // Send the Request document and set the response to this instance.
            return ($this->response = $service->send($request));
        }
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
        //return new ProcessXmlRequestService($this->getLogin());
        return $this->processXmlRequestService;
    }

    /**
     * Should be called by the child classes. Will set the response
     * document from an attempted SOAP request.
     * 
     * @access public
     * @param Response $response
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
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
