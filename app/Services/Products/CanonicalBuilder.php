<?php

namespace App\Services\Products;

use SimpleXMLElement;

class CanonicalBuilder
{
    public static function build(SimpleXMLElement $xml): array
    {
        $categoryRaw = (string)($xml->CATEGORYTEXT ?? '');
        $segments = CategoryPath::normalize($categoryRaw);
        $display = CategoryPath::display($segments);
        $hash = CategoryPath::hash($segments);
        $price = self::priceToCents((string)$xml->PRICE_VAT);
        $availabilityCode = trim((string)$xml->DELIVERY_DATE);
        $map = config('products.availability_map');
        $availabilityText = $map[$availabilityCode] ?? 'Neznámo';

        return [
            'external_id' => (string)$xml->ITEM_ID,
            'group_id' => ($g = trim((string)$xml->ITEMGROUP_ID)) !== '' ? strtoupper($g) : null,
            'name' => trim((string)$xml->PRODUCTNAME),
            'description' => self::cleanDesc((string)$xml->DESCRIPTION),
            'price_vat_cents' => $price,
            'currency' => 'CZK',
            'manufacturer' => ($m = trim((string)$xml->MANUFACTURER)) !== '' ? $m : null,
            'ean' => ($e = trim((string)$xml->EAN)) !== '' ? $e : null,
            'category_path' => $display,
            'category_hash' => $hash,
            'url' => (string)$xml->URL,
            'image_url' => ($i = trim((string)$xml->IMGURL)) !== '' ? $i : null,
            'availability_code' => $availabilityCode === '' ? null : $availabilityCode,
            'availability_text' => $availabilityText,
        ];
    }

    public static function hash(array $canonical): string
    {
        ksort($canonical);
        return sha1(json_encode($canonical, JSON_UNESCAPED_UNICODE));
    }

    private static function priceToCents(string $raw): int
    {
        $n = str_replace(',', '.', trim($raw));
        if ($n === '') return 0;
        return (int) round(((float)$n) * 100);
    }

    private static function cleanDesc(string $raw): ?string
    {
        $t = trim(preg_replace('/\s+/', ' ', strip_tags($raw)) ?? '');
        return $t !== '' ? $t : null;
    }
}
