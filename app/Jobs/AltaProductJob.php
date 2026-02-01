<?php

namespace App\Jobs;

use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductFullSpecificationItem;
use App\Models\Product\ProductFullSpecificationSection;
use App\Models\Product\ProductImage;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductShortSpecification;
use App\Models\Product\ProductTranslation;
use App\Models\Product\ProductVariation;
use App\Models\Product\ProductVariationItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\Translation\GoogleTranslation;
use Exception;

class AltaProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $productData;
    protected array $productAvailability;

    // ✅ Improved retry settings
    public int $tries = 3;
    public int $timeout = 300;  // ✅ Increased to 5 minutes
    public int $maxExceptions = 3;
    public int $backoffMultiplier = 2;

    public function __construct(array $productData, array $productAvailability = [])
    {
        $this->productData = $productData;
        $this->productAvailability = $productAvailability;
    }

    /**
     * ✅ Execute the job
     */
    public function handle(): void
    {
        Log::info($this->productData);
    }
}