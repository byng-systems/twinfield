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
use Pronamic\Twinfield\Factory\ParentFactory;
use Pronamic\Twinfield\Request\Read as Read;



/**
 * BrowseFactory
 * 
 * @author M.D.Ward <matthew.ward@byng-systems.com>
 */
class BrowseFactory extends ParentFactory
{
    
    public function get($code, $office = null)
    {
        if ($this->getLogin()->process()) {

            // select the correct company
//            $this->getLogin()->selectCompany($office);

            $browseRequest = new Read\Browse(
                ($office ?: $this->getConfig()->getOffice()),
                $code
            );


            $browseResponse = $this->getService()->send($browseRequest);
            $browseResponseDocument = $browseResponse->getResponseDocument();

//            $browseResponseDocument->formatOutput = true;
//            return $browseResponseDocument;

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
            
            file_put_contents("c:/www/tmp/request.xml", $finalDocument->saveXML());
            
            $response = $this->getService()->send($finalDocument);
            
            if ($response->isSuccessful()) {
                $response->getResponseDocument()->formatOutput = true;
                return $response->getResponseDocument();
            }
        }
    }
    
}
