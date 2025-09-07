<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = Product::query();
        $q->filterManufacturer($request->string('manufacturer'))
          ->filterAvailability($request->string('availability'))
          ->search($request->string('q'));
        if ($hash = $request->string('category_hash')) {
            $q->filterCategory($hash);
        }
        $products = $q->orderByDesc('id')->paginate(25)->withQueryString();

        // Stats for header panels
        $totalProducts = Product::count();
        $addedThisMonth = Product::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $availabilityCountsRaw = Product::select('availability_code')
            ->selectRaw('COUNT(*) as c')
            ->groupBy('availability_code')
            ->pluck('c','availability_code');
        $availabilityMap = config('products.availability_map');
        $availabilityStats = collect($availabilityMap)->map(function($label,$code) use ($availabilityCountsRaw){
            return [
                'code' => $code,
                'label' => $label === '' ? 'Neznámo' : $label,
                'count' => (int) ($availabilityCountsRaw[$code] ?? 0),
            ];
        })->values();

        return view('products.index', [
            'products' => $products,
            'filters' => $request->only(['q','manufacturer','availability','category_hash']),
            'stats' => [
                'total' => $totalProducts,
                'added_this_month' => $addedThisMonth,
                'availability' => $availabilityStats,
            ]
        ]);
    }

    public function show(Product $product)
    {
        $priceHistory = $product->priceChanges()->orderByDesc('changed_at')->limit(50)->get();
        $availabilityHistory = $product->availabilityChanges()->orderByDesc('changed_at')->limit(50)->get();
        return view('products.show', compact('product','priceHistory','availabilityHistory'));
    }
}
