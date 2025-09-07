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
        return view('products.index', [
            'products' => $products,
            'filters' => $request->only(['q','manufacturer','availability','category_hash'])
        ]);
    }

    public function show(Product $product)
    {
        $priceHistory = $product->priceChanges()->orderByDesc('changed_at')->limit(50)->get();
        $availabilityHistory = $product->availabilityChanges()->orderByDesc('changed_at')->limit(50)->get();
        return view('products.show', compact('product','priceHistory','availabilityHistory'));
    }
}
