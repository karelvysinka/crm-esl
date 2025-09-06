<?php

namespace App\Services\Products;

class CategoryPath
{
    public static function normalize(string $raw): array
    {
        $parts = array_filter(array_map(fn($p)=>trim($p), explode('|', $raw)));
        return array_values($parts);
    }

    public static function display(array $segments): string
    { return implode(' > ', $segments); }

    public static function hash(array $segments): string
    { return sha1(strtolower(implode('|', $segments))); }
}
