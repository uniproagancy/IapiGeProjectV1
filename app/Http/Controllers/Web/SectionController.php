<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product\ProductSection;

class SectionController extends Controller
{
    public function view(int $id)
    {
        $section = ProductSection::with([
            'products' => function ($q) {
                $q->where('show', 1)
                    ->where('active', 1)
                    ->with(['translations', 'price', 'images']);
            },
            'category',
        ])->findOrFail($id);

        $products = $section->products;

        return view('web.sections.view', compact('section', 'products'));
    }
}