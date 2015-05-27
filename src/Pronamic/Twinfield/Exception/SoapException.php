<?php
/**
 * SoapException.php
 * Definition of class SoapException
 * 
 * Created 26-May-2015 18:30:22
 *
 * @author M.D.Ward <matthew.ward@byng.co>
 * @copyright (c) 2015, Byng Services Ltd
 */
namespace Pronamic\Twinfield\Exception;

use SoapFault;



/**
 * SoapException
 * 
 * @author M.D.Ward <matthew.ward@byng.co>
 */
abstract class SoapException extends SoapFault implements ExceptionInterface
{
    
    public function __construct(SoapFault $previous)
    {
        parent::SoapFault($previous->getCode(), $previous->getMessage());
    }
}
