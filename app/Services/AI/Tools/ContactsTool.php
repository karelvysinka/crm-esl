<?php

namespace App\Services\AI\Tools;

use Illuminate\Support\Facades\DB;

class ContactsTool
{
    // Very simple read-only tools for MVP
    public function searchByText(string $q, int $limit = 5): array
    {
        $clean = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{FEFF}\x{00A0}]+/u', '', $q);
        $needle = mb_strtolower(trim($clean));
        if ($needle === '') { return []; }
        $like = '%'.str_replace(['%','_'], ['\\%','\\_'], $needle).'%';
        $rows = DB::table('contacts')
            ->select('id','first_name','last_name','email','phone')
            ->where(function($w) use ($like) {
                $w->whereRaw('LOWER(CONCAT_WS(" ", first_name, last_name)) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(email) LIKE ?', [$like])
                  ->orWhere('normalized_email', 'like', $like)
                  ->orWhereRaw('LOWER(phone) LIKE ?', [$like]);
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();
        return array_map(function($r){ return (array)$r; }, $rows->all());
    }

    public function findByEmail(string $email): array
    {
        // Normalize input (lowercase, trim, remove zero-width and NBSP)
        $clean = preg_replace('/[\x{200B}\x{200C}\x{200D}\x{FEFF}\x{00A0}]+/u', '', $email);
        $norm = mb_strtolower(trim($clean));
        if ($norm === '' || str_contains($norm, '@placeholder.local') || str_starts_with($norm, 'noemail-')) {
            return [];
        }
        // Prefer normalized_email (filled by importer/normalizer)
        $row = DB::table('contacts')->where('normalized_email', $norm)->first();
        if (!$row) {
            // Fallback 1: direct equality on email (collation usually case-insensitive)
            $row = DB::table('contacts')->where('email', $norm)->first();
        }
        if (!$row) {
            // Fallback 2: case-insensitive compare with TRIM to ignore stray spaces
            $row = DB::table('contacts')->whereRaw('TRIM(LOWER(email)) = ?', [$norm])->first();
        }
        if (!$row) {
            // Fallback 3: ignore internal spaces and NBSP in stored email for pathological data
            $row = DB::table('contacts')->whereRaw(
                "REPLACE(REPLACE(LOWER(email), ' ', ''), CHAR(160), '') = ?",
                [str_replace(["\xC2\xA0", ' '], '', $norm)]
            )->first();
        }
        if (!$row) {
            // Fallback 4: strip common zero-width characters (U+200B/C/D, U+FEFF) in stored email
            // Note: MySQL utf8mb4 CHAR(... USING utf8mb4) allows specifying these codepoints
            $sanitizedNorm = str_replace(["\xC2\xA0", ' ', "\xE2\x80\x8B", "\xE2\x80\x8C", "\xE2\x80\x8D", "\xEF\xBB\xBF"], '', $norm);
            $row = DB::table('contacts')->whereRaw(
                "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(email), ' ', ''), CHAR(160), ''), CHAR(8203 USING utf8mb4), ''), CHAR(8204 USING utf8mb4), ''), CHAR(8205 USING utf8mb4), '') = ?",
                [$sanitizedNorm]
            )->first();
            if (!$row) {
                // Also attempt removing BOM (U+FEFF)
                $row = DB::table('contacts')->whereRaw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(email), ' ', ''), CHAR(160), ''), CHAR(8203 USING utf8mb4), ''), CHAR(8204 USING utf8mb4), ''), CHAR(8205 USING utf8mb4), ''), CHAR(65279 USING utf8mb4), '') = ?",
                    [$sanitizedNorm]
                )->first();
            }
        }
        return $row ? (array)$row : [];
    }

    public function findByPhone(string $phone): array
    {
        // Normalize phone similar to importer
        $p = preg_replace('/[^0-9+]/', '', $phone);
        if ($p === '' || $p === null) { return []; }
        // Normalize Czech numbers missing +420
        if ($p && !str_starts_with($p, '+')) {
            $digits = preg_replace('/\D/', '', $p);
            if ($digits && preg_match('/^420?\d{9}$/', $digits)) {
                if (strlen($digits) === 12 && str_starts_with($digits, '420')) { $p = '+'.$digits; }
                elseif (strlen($digits) === 9) { $p = '+420'.$digits; }
            }
        }
        $row = DB::table('contacts')->where('normalized_phone', $p)->first();
        return $row ? (array)$row : [];
    }
}
