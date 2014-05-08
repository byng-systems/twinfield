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
    
    public function get($code, $office = null)
    {
        $browseRequest = new Read\Browse(
            ($office ?: $this->getConfig()->getOffice()),
            $code
        );

        $browseResponse = $this->execute($browseRequest);        
        $browseResponseDocument = $browseResponse->getResponseDocument();

        $xpath = new DOMXPath($browseResponseDocument);
        
        $finalDocument = new DOMDocument();
        $finalDocument->appendChild($finalDocument->createElement("columns"));
            
        /* @var $columnElement \DOMElement */
        /* @var $childNode \DOMElement */
        foreach ($xpath->query("/browse/columns/column") as $columnElement) {
            
            $finalColumn = $finalDocument->createElement("column");

            $isOfficeField = false;
            foreach ($xpath->query("./field | ./label | ./visible | ./from | ./to", $columnElement) as $finalField) {
                $importNode = $finalDocument->importNode($finalField, true);
                $tag = $importNode->nodeName;

                if ($importNode->nodeValue == "fin.trs.head.office") {
                    $isOfficeField = true;
                }
                if ($isOfficeField) {
                    if ($importNode->nodeName == "from") {
                        $importNode->nodeValue = $office;
                    }
                    if ($importNode->nodeName == "to") {
                        $importNode->nodeValue = $office;
                    }
                }
                $finalColumn->appendChild($importNode);
            }
            
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
        }
    }
    
}
