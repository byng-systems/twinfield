<?php
namespace Pronamic\Twinfield\Secure;

use DateInterval;
use DateTime;
use DOMDocument;
use Pronamic\Twinfield\SoapClient;
use SoapFault;
use SoapHeader;

/**
 * Login Class.
 *
 * Used to return an instance of a Soapclient for further interaction
 * with Twinfield services.
 *
 * The username, password and organisation are retrieved from the options
 * on construct.
 *
 * @uses \Pronamic\Twinfield\Secure\Config    Holds all the config settings for this account
 * @uses \SoapClient                          For both login and future interactions
 * @uses \SoapHeader                          Generation of the secure header
 * @uses \DOMDocument                         Handles the response from login
 *
 * @since 0.0.1
 *
 * @package Pronamic\Twinfield
 * @subpackage Secure
 * @author Leon Rowland <leon@rowland.nl>
 * @copyright (c) 2013, Leon Rowland
 * @version 0.0.1
 */
class SessionLoginHandler extends AbstractAuthenticationHandler
{
    /**
     * Set session timeout interval to 60 minutes
     */
    const SESSION_TIMEOUT_INTERVAL = 'PT1H';

    /**
     * Fully qualified URL to the Twinfield login WSDL document
     */
    const LOGIN_WSDL_URL = 'https://login.twinfield.com/webservices/session.asmx?wsdl';

    /**
     * URI for the processxml WSDL document on the assigned Twinfield cluster
     */
    const PROCESSXML_WSDL_URI = '/webservices/processxml.asmx?wsdl';

    /**
     * URI for the processxml WSDL document on the assigned Twinfield cluster
     */
    const KEEPALIVE_WSDL_URI = '/webservices/session.asmx?wsdl';



    /**
     *
     * @var SoapClient
     */
    protected $keepAliveSoapClient;

    /**
     * Holds the passed in Config instance
     * 
     * @access private
     * @var Config
     */
    protected $config;

    /**
     * The sessionID for the successful login
     *
     * @access private
     * @var string
     */
    private $sessionID;

    /**
     * The server cluster used for future XML
     * requests with the new SoapClient
     *
     * @access private
     * @var string
     */
    private $cluster;

    /**
     * If the login has been processed and was
     * successful
     *
     * @access private
     * @var DateTime
     */
    private $loginExpiry = null;
    
    
    
    /**
     * 
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        
        parent::__construct($this->buildLoginSoapClient());
        
        $this->keepAliveSoapClient = $this->buildSessionSoapClient();
    }
    
    /**
     * 
     * @return array
     */
    public function __sleep()
    {
        return array_diff(
            array_keys(get_object_vars($this)),
            ['authenticationSoapClient', 'keepAliveSoapClient']
        );
    }

    /**
     * Handles actions on sleep
     */
    public function __wakeup()
    {
        $this->authenticationSoapClient = $this->buildLoginSoapClient();
        $this->keepAliveSoapClient = $this->buildSessionSoapClient();
    }
    
    /**
     * 
     * @return SoapClient
     */
    private function buildLoginSoapClient()
    {
        return new SoapClient(
            static::LOGIN_WSDL_URL,
            ['trace' => 1]
        );
    }
    
    /**
     * 
     * @return SoapClient|null
     */
    private function buildSessionSoapClient()
    {
        if (!$this->isProcessed()) {
            return null;
        }
        
        $soapClient = new SoapClient(
            $this->cluster . static::KEEPALIVE_WSDL_URI,
            ['trace' => 1]
        );

        $soapClient->__setSoapHeaders(
            new SoapHeader(
                'http://www.twinfield.com/',
                'Header',
                ['SessionID' => $this->sessionID]
            )
        );

        return $soapClient;
    }

    /**
     * 
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }
    
    /**
     * 
     * @return boolean
     */
    public function isProcessed()
    {
        return (
            $this->loginExpiry !== null
            && new DateTime() < $this->loginExpiry
        );
    }
    
    /**
     * 
     */
    private function updateSessionExpiryDate()
    {
        $this->loginExpiry = new DateTime();
        $this->loginExpiry->add(new DateInterval(static::SESSION_TIMEOUT_INTERVAL));
    }
    
    /**
     * Will process the login.
     *
     * If successful, will set the session and cluster information
     * to the class
     *
     * @since 0.0.1
     *
     * @access public
     * @param boolean $keepAlive Should we force a keep-alive request?
     * @return boolean If successful or not
     */
    public function process($keepAlive = false)
    {
        if ($this->isProcessed() === true) {
            return ($keepAlive === false) || $this->keepAlive();
        }

        return $this->login();
    }
    
    protected function keepAlive()
    {
        /*
         * This whole class needs refactoring - this hacky internal management approach is bad
         */
        if ($this->keepAliveSoapClient !== null) {
            try {
                $this->keepAliveSoapClient->KeepAlive();
                $this->updateSessionExpiryDate();

                return true;
            } catch (SoapFault $ex) {
                return $this->login();
            }
        }
        
        return true;
    }
    
    /**
     * Will forceably renew the login
     * 
     * If successful, will set the session and cluster information to the object
     * 
     * @return boolean
     */
    public function login()
    {
        // Process logon
        $response = $this->authenticationSoapClient->Logon($this->config->getCredentials());

        // Check response is successful
        if('Ok' === $response->LogonResult) {
            // Make a new DOM and load the response XML
            $envelope = new DOMDocument();
            $envelope->loadXML($this->authenticationSoapClient->__getLastResponse());

            // Gets SessionID
            $sessionID       = $envelope->getElementsByTagName('SessionID');
            $this->sessionID = $sessionID->item(0)->textContent;

            // Gets Cluster URL
            $cluster       = $envelope->getElementsByTagName('cluster');
            $this->cluster = $cluster->item(0)->textContent;
            
            // This login object is processed!
            $this->updateSessionExpiryDate();
            
            $this->keepAliveSoapClient = $this->buildSessionSoapClient();

            return true;
        }

        return false;
    }

    /**
     * Gets a new instance of the soap header.
     *
     * Will automaticly login if haven't already on this instance
     *
     * @since 0.0.1
     *
     * @access public
     * @return SoapHeader
     */
    public function getHeader()
    {
        $this->process();

        return new SoapHeader(
            'http://www.twinfield.com/',
            'Header',
            ['SessionID' => $this->sessionID]
        );
    }

    /**
     * Gets the soap client with the headers attached
     *
     * Will automaticly login if haven't already on this instance
     *
     * @since 0.0.1
     *
     * @access public
     * @return \SoapClient
     */
    public function getClient()
    {
        $this->process();

        // Makes a new client, and assigns the header to it
        $client = new SoapClient($this->cluster . static::PROCESSXML_WSDL_URI);
        $client->__setSoapHeaders($this->getHeader());

        return $client;
    }
    
    /**
     * 
     * @return string
     */
    public function getSessionId()
    {
        $this->process();

        return $this->sessionID;
    }
    
    /**
     * 
     * @return string
     */
    public function getCluster()
    {
        $this->process();

        return $this->cluster;
    }
}
