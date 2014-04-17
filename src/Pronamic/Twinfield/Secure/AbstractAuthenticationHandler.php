<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 17/04/14
 * Time: 17:26
 */

namespace Pronamic\Twinfield\Secure;


use Pronamic\Twinfield\SoapClient;

abstract class AbstractAuthenticationHandler {

    /**
     * The SoapClient used to login to Twinfield
     *
     * @var SoapClient
     */
    protected $authenticationSoapClient;
    protected $config;

    public function __construct(SoapClient $authenticationSoapClient) {
        $this->authenticationSoapClient = $authenticationSoapClient;
    }

    abstract function setConfig(Config $config);

    abstract function process();

} 