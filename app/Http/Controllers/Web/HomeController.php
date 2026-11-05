<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Modules\Product\Models\Product;
use Illuminate\View\View;

final class HomeController
{
    public function index(): View
    {
        $products = Product::where('status', 1)->latest()->take(12)->get();
        return view('web.home', compact('products'));
    }
}
