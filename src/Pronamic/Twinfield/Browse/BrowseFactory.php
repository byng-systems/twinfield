<?php
/**
 * BrowseFactory.php
 * Definition of class BrowseFactory
 * 
 * Created 14-Mar-2014 16:32:25
 *
 * @author M.D.Ward <matthew.ward@byng-systems.com>
 * @copyright (c) 2014, Byng Systems/SkillsWeb Ltd
 */

namespace Pronamic\Twinfield\Browse;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMXPath;
use SimpleXMLElement;
use Pronamic\Twinfield\Request\Read as Read;
use Pronamic\Twinfield\Factory\ProcessXmlRequestFactory;
use Pronamic\Twinfield\Exception\RequestFailedException;

/**
 * BrowseFactory
 * 
 * @author M.D.Ward <matthew.ward@byng-systems.com>
 */
class BrowseFactory extends ProcessXmlRequestFactory
{
    protected $resultKey = "label";
    
    const KEY_LABEL = "label";
    const KEY_VALUE = "value";
   
    /**
     * Set the value to use for the results array keys.
     * 
     * @param string $resultKey label = descriptive column name e.g. Company
     *                          value = twinfields column name e.g. fin.trs.head.office
     * 
     * @return \Pronamic\Twinfield\Browse\BrowseFactory
     */
    public function setResultArrayKey($resultKey)
    {
        if($resultKey !== static::KEY_LABEL && $resultKey !== static::KEY_VALUE) {
            $this->resultKey = static::KEY_LABEL;
        } else {
            $this->resultKey = $resultKey;
        }
        
        return $this;
    }
    
    /**
     * 
     * @param string $code
     * @param string $office
     * 
     * @return \DOMDocument
     * 
     * @throws RequestFailedException
     */
    public function getBrowseColumnsResponse($code, $office = null)
    {
        $browseRequest = new Read\Browse(
            ($office ?: $this->getConfig()->getOffice()),
            $code
        );
        
        $browseResponse = $this->execute($browseRequest);
        
        if($browseResponse === null) {
            throw new RequestFailedException("Incorrect login credentials");
        }
        
        return $browseResponse->getResponseDocument();
    }
    
    /**
     * 
     * @param string $code
     * @param string $office
     * @param array  $extras
     * 
     * @return array
     * 
     * @throws RequestFailedException
     */
    public function get($code, $office = null, $extras = array())
    {
        $extras["fin.trs.head.office"] = array(
            "label" => "Office",
            "from" => $office,
            "operator" => "equal",
            "visible" => "true"
        );

        $browseResponseDocument = $this->getBrowseColumnsResponse($code, $office);

        $xpath = new DOMXPath($browseResponseDocument);
        
        $finalDocument = new DOMDocument();
        $finalDocument->appendChild($finalDocument->createElement("columns"));
            
        /* @var $columnElement \DOMElement */
        /* @var $childNode \DOMElement */
        // Loop through each <column>..
        $finds = array();
        foreach ($xpath->query("/browse/columns/column") as $columnElement) {
            
            $finalColumn = $finalDocument->createElement("column");

            // Loop through all <field>s in the <column> (should only be one) ..
            foreach ($xpath->query("./field", $columnElement) as $finalField) {
                $found = null; 
                $importField = $finalDocument->importNode($finalField, true);
                
                // Add field to the column
                $finalColumn->appendChild($importField);
                
                // If we find the field in the extras array, mark it down..
                if (isset($extras[$importField->nodeValue])) {
                    $finds[] = $found = $importField->nodeValue;
                }
                
                // Loop through other elements in the <column>..
                foreach ($xpath->query("./label | ./visible | ./from | ./to | ./operator ", $columnElement) as $finalItems) {
                    $importNode = $finalDocument->importNode($finalItems, true);
                    
                    // If we have overrides in the extras structure, apply them
                    if ($found && isset($extras[$found][$importNode->nodeName])) {
                        $importNode->nodeValue = $extras[$found][$importNode->nodeName];
                    }
                    
                    // Add item to the <column>
                    $finalColumn->appendChild($importNode);
                }
            }
            
            // Add <column> to the final document
            $finalDocument->documentElement->appendChild($finalColumn);
        }
        
        // Loop through extras that weren't found in the browseResponseDocument..
        foreach (array_diff(array_keys($extras), $finds) as $key) {
            $finalColumn = $finalDocument->createElement("column");
            
            // Add in the field element in case it was forgotten
            $extras[$key]["field"] = $key;
            
            // Loop through each field and add it to the column
            foreach ($extras[$key] as $tag => $value) {
                $element = $finalDocument->createElement($tag);
                $element->nodeValue = $value;
                $finalColumn->appendChild($element);
            }
            
            // Add the column to the document
            $finalDocument->documentElement->appendChild($finalColumn);
        }

            
        $finalDocument->documentElement->appendChild(new DOMAttr("code", $code));
        $finalDocument->formatOutput = true;
        
        $response = $this->execute($finalDocument);
        
        if ($response->isSuccessful()) {
            $response->getResponseDocument()->formatOutput = true;
            $responseDocument = $response->getResponseDocument();

            // Munge to SimpleXML
            $xmlResponse = new SimpleXMLElement($responseDocument->saveXML());

            // Get all the data rows out
            $data = array();
            foreach($xmlResponse->xpath("tr") as $xmlRow) {
                $dataRow = array();
                foreach ($xmlRow->xpath("td") as $value) {
                    $dataRow[] = (string) $value;
                }
                $data[] = $dataRow;
            }

            // Get the key labels out
            $keys = array();
            foreach ($xmlResponse->xpath("th/td") as $header) {
                if($this->resultKey === static::KEY_LABEL) {
                    $keys[] = (string) $header->attributes()->label;
                } else {
                    $keys[] = (string) $header;
                }
            }

            // Convert from CSV style to indexed arrays
            $rotated = array();
            foreach ($data as $xmlRow) {
                $dataRow = array();
                foreach ($xmlRow as $index => $value) {
                    $dataRow[$keys[$index]] = $value;
                }
                $rotated[] = (object) $dataRow;
            }


            return $rotated;
        } else {
            $reason = "No error message received";
            $messages = $response->getErrorMessages();
            if (count($messages) === 1) {
                $reason = $messages[0];
            } else {
                $reason = json_encode($messages);
            }
            throw new RequestFailedException("Failed to get from soap endpoint. Twinfields said: " . $reason);
        }
    }
    
}
