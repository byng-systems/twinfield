<?php

namespace Pronamic\Twinfield\Transaction;

use \Pronamic\Twinfield\Request as Request;
use Pronamic\Twinfield\Factory\ProcessXmlRequestFactory;

/**
 * TransactionFactory class
 * 
 * @author Dylan Schoenmakers <dylan@opifer.nl>
 */
class TransactionFactory extends ProcessXmlRequestFactory
{
    
    public function get($code, $number, $office = null)
    {
        // Get the secure service
        $service = $this->getService();

        if(!$office)
            $office = $this->getConfig()->getOffice();

        // Make a request to read a single transaction
        $request_transaction = new Request\Read\Transaction();
        $request_transaction
            ->setCode($code)
            ->setNumber($number)
            ->setOffice($office);

        // Send the Request document and set the response to this instance
        $response = $service->send($request_transaction);
        $this->setResponse($response);

        if($response->isSuccessful()) {
            return Mapper\TransactionMapper::map($response);
        } else {
            return false;
        }
    }

    /**
     * 
     * @param \Pronamic\Twinfield\Transaction\Transaction $transaction
     * @return boolean
     */
    public function send(Transaction $transaction)
    {
        if ($this->getLogin()->process()) {
            
            // Gets the secure service
            $service = $this->getService();

            $transactionsDocument = new DOM\TransactionsDocument();
            $transactionsDocument->addTransaction($transaction);

            // Send the DOM document request and set the response
            $response = $service->send($transactionsDocument);
            $this->setResponse($response);

            if ($response->isSuccessful()) {
                return true;
            } else {
                // should throw exception?
                return false;
            }
        }
    }

}
