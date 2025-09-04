<?php

namespace App\Services\AI\Tools;

use Illuminate\Support\Facades\DB;

class CompaniesTool
{
    /**
     * Search companies by free text (name, email, phone, website, city)
     * Returns array of rows: id, name, email, phone
     */
    public function searchByText(string $q, int $limit = 5): array
    {
        $q = trim($q);
        if ($q === '') { return []; }
        $term = '%'.$q.'%';
        $rows = DB::table('companies')
            ->select('id','name','email','phone','website','city')
            ->where(function($qq) use ($term) {
                $qq->where('name', 'like', $term)
                   ->orWhere('email', 'like', $term)
                   ->orWhere('phone', 'like', $term)
                   ->orWhere('website', 'like', $term)
                   ->orWhere('city', 'like', $term);
            })
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function($r){ return (array)$r; })
            ->toArray();
        return $rows;
    }
}
