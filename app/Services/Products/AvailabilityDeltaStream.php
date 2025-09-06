<?php

namespace App\Services\Products;

use XMLReader;

class AvailabilityDeltaStream implements \IteratorAggregate
{
    public function __construct(private string $file) {}

    public function getIterator(): \Traversable
    {
        $r = new XMLReader();
        if(!$r->open($this->file)) {
            throw new \RuntimeException("Nelze otevřít XML soubor: {$this->file}");
        }
        while($r->read()) {
            if($r->nodeType === XMLReader::ELEMENT && $r->name === 'SHOPITEM') {
                $fragment = $r->readOuterXML();
                $item = @simplexml_load_string($fragment);
                if(!$item) { continue; }
                $id = trim((string) ($item->ITEM_ID ?? ''));
                if($id==='') { continue; }
                yield [
                    'external_id' => $id,
                    'availability_code' => trim((string) ($item->AVAILABILITY ?? '')) ?: null,
                    'availability_text' => trim((string) ($item->DELIVERY_DATE ?? '')) ?: null,
                    'stock_quantity' => ($sq = trim((string) ($item->STOCK_QUANTITY ?? ''))) !== '' ? (int)$sq : null,
                ];
            }
        }
        $r->close();
    }
}
