<?php

namespace App\Services\Products;

use XMLReader;

class HeurekaProductStream implements \IteratorAggregate
{
    public function __construct(private string $filePath) {}

    public function getIterator(): \Traversable
    {
        $reader = new XMLReader();
        $reader->open($this->filePath, 'UTF-8');
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'SHOPITEM') {
                $node = $reader->expand();
                if(!$node) { continue; }
                // XMLReader::expand může vrátit DOMNode bez asociovaného ownerDocument v některých edge případech.
                $doc = new \DOMDocument('1.0', 'UTF-8');
                $doc->appendChild($doc->importNode($node, true));
                $xml = simplexml_load_string($doc->saveXML());
                if($xml) {
                    yield $xml;
                }
            }
        }
        $reader->close();
    }
}
