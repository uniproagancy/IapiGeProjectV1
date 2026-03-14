<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Models\Product\ProductImage;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductTranslation;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductCategoryTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JsonParseController extends Controller
{
    public function loadJson()
    {
        $jsonPath = storage_path('app/Json2.json');
        $jsonContent = file_get_contents($jsonPath);
        $data = json_decode($jsonContent, true);
        foreach ($data['categories'] as $category) {

        }
    }
}