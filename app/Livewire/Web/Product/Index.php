<?php

namespace App\Livewire\Web\Product;

use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductSection;
use App\Models\Product\ProductFullSpecificationSection;
use App\Support\SpecValue;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    /** რამდენ უნიკალურ რიცხვზე ვრთავთ სლაიდერს checkbox-ების ნაცვლად */
    private const SPEC_SLIDER_THRESHOLD = 8;

    public $parentCategories = [];
    public $subCategories    = [];
    public $currentCategory  = null;
    public $selectedParent   = null;
    public $showAllBrands    = false;
    public $category_slug    = null;
    public bool $isLoading   = false;
    public string $eventId   = '';
    public array $categoryProductIds = [];

    #[Url]
    public $search = '';

    #[Url(as: 'brands', keep: true)]
    public $selectedBrands = [];

    #[Url(as: 'min', keep: true)]
    public $priceMin = null;

    #[Url(as: 'max', keep: true)]
    public $priceMax = null;

    #[Url(as: 'specs', keep: true)]
    public $selectedSpecs = [];

    #[Url]
    public bool $onlyDiscounted = false;

    #[Url(as: 'sort', keep: true)]
    public string $sort = 'newest';

    #[Url(as: 'show', keep: true)]
    public $perPage = 20;

    public function mount($category_slug = null): void
    {
        $this->category_slug = $category_slug;
        $this->loadParentCategories();
        $this->loadCategoryFromSlug();
        $this->normalizeBrands();
        $this->normalizeSpecs();
        $this->normalizePerPage();
        // ✅ იგივე event_id, რაც middleware-მ CAPI-ს გაუგზავნა — თორემ ორ ივენთად ითვლება
        $this->eventId = view()->shared('fb_event_id', 'pv_' . time() . '_' . Str::random(6));
    }

    private function normalizeBrands(): void
    {
        if (is_string($this->selectedBrands)) {
            $this->selectedBrands = $this->selectedBrands
                ? explode(',', $this->selectedBrands)
                : [];
        }

        $this->selectedBrands = array_filter(array_map('intval', (array) $this->selectedBrands));
    }

    private function normalizeSpecs(): void
    {
        if (is_string($this->selectedSpecs)) {
            $this->selectedSpecs = $this->selectedSpecs
                ? explode(',', $this->selectedSpecs)
                : [];
        }

        $this->selectedSpecs = array_filter($this->selectedSpecs);
    }

    private function normalizePerPage(): void
    {
        $this->perPage = max(20, (int) $this->perPage);
    }

    private function loadParentCategories(): void
    {
        $this->parentCategories = cache()->remember('parent_categories_with_products', 3600, fn () =>
        ProductCategory::query()
            ->where('parent_id', 0)
            ->where('show', 1)
            ->where(function ($q) {
                $q->whereHas('products', fn ($p) => $p->where('show', 1)->where('active', 1))
                    ->orWhereHas('children.products', fn ($p) => $p->where('show', 1)->where('active', 1));
            })
            ->orderBy('sortable')
            ->get()
        );
    }

    private function loadCategoryFromSlug(): void
    {
        if (empty($this->category_slug)) {
            return;
        }

        $category = ProductCategory::query()
            ->whereHas('translations', function ($query) {
                $query->where('slug', $this->category_slug);
            })
            ->first();

        if (!$category) {
            abort(404);
        }

        $this->currentCategory = $category;
        $this->loadSubcategories($category);
    }

    private function loadSubcategories(ProductCategory $category): void
    {
        if ($category->parent_id === 0) {
            $this->selectedParent = $category->id;
            $this->subCategories  = $category->children()
                ->where('show', 1)
                ->whereHas('products', fn ($p) => $p->where('show', 1)->where('active', 1))
                ->get();
        } else {
            $this->selectedParent = $category->parent_id;
            $this->subCategories  = ProductCategory::query()
                ->where('parent_id', $category->parent_id)
                ->where('show', 1)
                ->whereHas('products', fn ($p) => $p->where('show', 1)->where('active', 1))
                ->get();
        }
    }

    public function toggleShowAllBrands(): void
    {
        $this->showAllBrands = !$this->showAllBrands;
    }

    public function selectParent($id): void
    {
        $this->selectCategoryAndRedirect($id);
    }

    public function selectChild($id): void
    {
        $this->selectCategoryAndRedirect($id);
    }

    private function selectCategoryAndRedirect($categoryId): void
    {
        $category = ProductCategory::find($categoryId);
        if (!$category) return;
        $this->isLoading = true;
        $this->redirectToCategory($category);
    }

    public function resetCategories(): void
    {
        $this->isLoading = true;
        $this->redirect(route('web.products.index'));
    }

    private function redirectToCategory(ProductCategory $category): void
    {
        $url = route('web.products.index', [
            'category_slug' => $category->translation('ka')->slug,
        ]);

        // ✅ კატეგორიის შეცვლისას ფილტრები ნულდება.
        //    წინა კატეგორიის ბრენდი ან სპეციფიკაცია („ფართი: 30-40 მ²") ახალში
        //    არ არსებობს, ამიტომ მომხმარებელს ცარიელი კატეგორია ხვდებოდა.
        //    ჩვენების პარამეტრები (დალაგება, გვერდზე რაოდენობა) რჩება.
        $params = $this->buildDisplayParams();

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $this->redirect($url);
    }

    /**
     * მხოლოდ ჩვენების პარამეტრები — პროდუქტების კრებულს არ ავიწროებს.
     */
    private function buildDisplayParams(): array
    {
        return array_filter([
            'sort' => $this->sort !== 'newest' ? $this->sort : null,
            'show' => $this->perPage !== 20 ? $this->perPage : null,
        ]);
    }

    public function clearPriceFilter(): void
    {
        $this->priceMin  = null;
        $this->priceMax  = null;
        $this->resetPage();
        $this->isLoading = true;
    }

    public function clearBrandFilter(): void
    {
        $this->selectedBrands = [];
        $this->resetPage();
        $this->isLoading = true;
    }

    public function clearSpecFilter(): void
    {
        $this->selectedSpecs = [];
        $this->resetPage();
        $this->isLoading = true;
    }

    public function clearDiscountFilter(): void
    {
        $this->onlyDiscounted = false;
        $this->resetPage();
        $this->isLoading = true;
    }

    public function resetAllFilters(): void
    {
        $this->selectedBrands  = [];
        $this->priceMin        = null;
        $this->priceMax        = null;
        $this->search          = '';
        $this->selectedSpecs   = [];
        $this->onlyDiscounted  = false;
        $this->sort            = 'newest';
        $this->perPage         = 20;
        $this->currentCategory = null;
        $this->selectedParent  = null;
        $this->resetPage();
        $this->isLoading = true;
    }

    public function updatedSelectedBrands(): void
    {
        $this->normalizeBrands();
        $this->resetPage();
        $this->isLoading = true;
    }

    public function updatedPriceMin(): void
    {
        $this->resetPage();
        $this->isLoading = true;
    }

    public function updatedPriceMax(): void
    {
        $this->resetPage();
        $this->isLoading = true;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->isLoading = true;
    }

    public function updatedSelectedSpecs(): void
    {
        $this->normalizeSpecs();
        $this->resetPage();
        $this->isLoading = true;
    }

    public function updatedOnlyDiscounted(): void
    {
        $this->resetPage();
        $this->isLoading = true;
    }

    public function updatedSort(): void
    {
        $this->resetPage();
        $this->isLoading = true;
    }

    public function loadMore(): void
    {
        $this->perPage += 20;
    }

    // ============================================
    // Price Range — კატეგორიის მიხედვით min/max
    // ============================================

    #[Computed(cache: false)]
    public function priceRange(): array
    {
        $query = Product::where('active', 1)
            ->where('show', 1)
            ->join('db_product_prices', 'db_product_prices.product_id', '=', 'db_products.id');

        if ($this->currentCategory) {
            if ($this->currentCategory->parent_id === 0) {
                $childIds = $this->currentCategory->children()->pluck('id');
                $query->whereIn('db_products.category_id', $childIds);
            } else {
                $query->where('db_products.category_id', $this->currentCategory->id);
            }
        }

        $result = $query->selectRaw('
            MIN(COALESCE(NULLIF(db_product_prices.discount_price, 0), db_product_prices.regular_price)) as min_price,
            MAX(COALESCE(NULLIF(db_product_prices.discount_price, 0), db_product_prices.regular_price)) as max_price
        ')->first();

        return [
            'min' => (int) floor($result->min_price ?? 0),
            'max' => (int) ceil($result->max_price ?? 50000),
        ];
    }

    // ============================================
    // Products
    // ============================================

    #[Computed(cache: false)]
    public function products()
    {
        $query = $this->buildProductQuery();

        switch ($this->sort) {
            case 'price_asc':
                $query->leftJoin('db_product_prices', 'db_product_prices.product_id', '=', 'db_products.id')
                    ->orderByRaw('COALESCE(NULLIF(db_product_prices.discount_price, 0), db_product_prices.regular_price) ASC');
                break;

            case 'price_desc':
                $query->leftJoin('db_product_prices', 'db_product_prices.product_id', '=', 'db_products.id')
                    ->orderByRaw('COALESCE(NULLIF(db_product_prices.discount_price, 0), db_product_prices.regular_price) DESC');
                break;

            case 'oldest':
                $query->orderBy('db_products.id', 'ASC');
                break;

            case 'newest':
            default:
                $query->orderBy('db_products.id', 'DESC');
                break;
        }

        $result          = $query->paginate($this->perPage);
        $this->isLoading = false;
        return $result;
    }

    #[Computed(cache: false)]
    public function brands()
    {
        return ProductBrand::query()
            ->select('id', 'logo', 'show', 'active')
            ->with(['translations' => fn ($q) => $q
                ->select('id', 'product_brand_id', 'title', 'slug', 'locale')
                ->where('locale', app()->getLocale())
            ])
            ->where('show', 1)
            ->where('active', 1)
            ->whereIn('id', function ($sub) {
                $sub->select('brand_id')
                    ->from('db_products')
                    ->where('show', 1)
                    ->where('active', 1)
                    ->whereNull('deleted_at')
                    ->whereNotNull('brand_id')
                    ->when($this->currentCategory, function ($q) {
                        if ($this->currentCategory->parent_id === 0) {
                            $childIds = $this->currentCategory->children()->pluck('id');
                            $q->whereIn('category_id', $childIds);
                        } else {
                            $q->where('category_id', $this->currentCategory->id);
                        }
                    })
                    ->when(!empty($this->priceMin), function ($q) {
                        $q->whereExists(function ($price) {
                            $price->select('id')->from('db_product_prices')
                                ->whereColumn('product_id', 'db_products.id')
                                ->whereNull('deleted_at')
                                ->whereRaw('COALESCE(NULLIF(discount_price, 0), regular_price) >= ?', [round((float) $this->priceMin, 2)]);
                        });
                    })
                    ->when(!empty($this->priceMax), function ($q) {
                        $q->whereExists(function ($price) {
                            $price->select('id')->from('db_product_prices')
                                ->whereColumn('product_id', 'db_products.id')
                                ->whereNull('deleted_at')
                                ->whereRaw('COALESCE(NULLIF(discount_price, 0), regular_price) <= ?', [round((float) $this->priceMax, 2)]);
                        });
                    })
                    ->when($this->onlyDiscounted, function ($q) {
                        $q->whereExists(function ($price) {
                            $price->select('id')->from('db_product_prices')
                                ->whereColumn('product_id', 'db_products.id')
                                ->whereNull('deleted_at')
                                ->whereNotNull('discount_price')
                                ->where('discount_price', '>', 0);
                        });
                    })
                    ->distinct();
            })
            ->get();
    }

    #[Computed]
    public function specificationSections()
    {
        if (empty($this->currentCategory)) {
            return collect();
        }

        $categoryId = $this->currentCategory->id;
        $parentId   = $this->currentCategory->parent_id;
        $cacheKey   = 'spec_sections_v3_' . $categoryId;

        return cache()->remember($cacheKey, 3600, function () use ($parentId) {
            $productIds = Product::query()
                ->where('show', 1)
                ->where('active', 1)
                ->when($parentId === 0, function ($q) {
                    $childIds = $this->currentCategory->children()->pluck('id');
                    $q->whereIn('category_id', $childIds);
                }, function ($q) {
                    $q->where('category_id', $this->currentCategory->id);
                })
                ->pluck('id');

            if ($productIds->isEmpty()) return collect();

            $items = \App\Models\Product\ProductFullSpecificationItem::query()
                ->select('id', 'section_id', 'name', 'value')
                ->whereNotNull('value')
                ->where('filter', 1)
                ->where('value', '!=', '')
                ->whereHas('section', function ($q) use ($productIds) {
                    $q->whereIn('product_id', $productIds);
                })
                ->get();

            if ($items->isEmpty()) return collect();

            return $items
                ->groupBy('name')
                ->reject(fn ($group, $name) => in_array(mb_strtolower($name), ['ბრენდი', 'brand'], true))
                ->map(fn ($group) => $this->buildSpecSection($group))
                ->filter(fn ($section) => $section !== null && count($section['values']) > 1);
        });
    }

    /**
     * ერთი სპეციფიკაციის ჯგუფი — ნორმალიზებული, ტიპით და ნედლი მნიშვნელობების რუკით.
     *
     * ნედლი მნიშვნელობები აუცილებელია: ბაზაში ისინი დაუმუშავებლად წევს,
     * ამიტომ ფილტრის მოთხოვნა სწორედ მათზე უნდა გაეშვას.
     */
    private function buildSpecSection($group): ?array
    {
        $rawValues = $group->pluck('value')->map(fn ($v) => (string) $v)->unique()->values();

        if ($rawValues->isEmpty()) return null;

        $unit      = SpecValue::dominantUnit($rawValues);
        $isNumeric = SpecValue::isNumericSpec($rawValues);

        // ნედლი → კანონიკური, რომ „12000" და „12000 BTU" ერთ ვარიანტად გაერთიანდეს
        $buckets = [];
        foreach ($rawValues as $raw) {
            $label = SpecValue::canonical($raw, $unit);
            if ($label === '') continue;

            $buckets[$label]['label']  = $label;
            $buckets[$label]['raw'][]  = $raw;
            $buckets[$label]['num']    = SpecValue::toNumber($raw);
        }

        if (empty($buckets)) return null;

        $values = collect($buckets)->values();

        // რიცხვითი — რიცხვით ვალაგებთ, თორემ „100-120" „15-20"-მდე მოდის
        $values = $isNumeric
            ? $values->sortBy(fn ($v) => $v['num'] ?? PHP_INT_MAX)->values()
            : $values->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)->values();

        // დიაპაზონები („30-40 მ²") უკვე დაჯგუფებულია — მათზე checkbox ჯობია.
        // ერთეული რიცხვები („250 ლ") ბევრია — იქ სლაიდერი გვინდა.
        $rangeLike = $rawValues->filter(fn ($v) => count(SpecValue::parse($v)['numbers']) >= 2)->count();
        $mostlyRanges = $rawValues->count() > 0 && ($rangeLike / $rawValues->count()) >= 0.6;

        // ბევრი ცალკეული რიცხვი (58 ლიტრაჟი 69 პროდუქტზე) checkbox-ად უსარგებლოა —
        // ავტომატურად ვაჯგუფებთ მრგვალ დიაპაზონებად.
        if ($isNumeric && !$mostlyRanges && $values->count() > self::SPEC_SLIDER_THRESHOLD) {
            $values = $this->bucketNumericValues($values, $unit);
        }

        $nums = $values->pluck('num')->filter(fn ($n) => $n !== null);

        return [
            'type'   => 'choice',
            'unit'   => $unit ? SpecValue::displayUnit($unit) : null,
            'min'    => $nums->isNotEmpty() ? (float) $nums->min() : null,
            'max'    => $nums->isNotEmpty() ? (float) $nums->max() : null,
            'values' => $values->all(),
        ];
    }

    /**
     * რიცხვითი მნიშვნელობების დაჯგუფება მრგვალ დიაპაზონებად.
     * „134 ლ, 194 ლ, 206 ლ …" → „100-200 L", „200-300 L" …
     */
    private function bucketNumericValues($values, ?string $unit)
    {
        $nums = $values->pluck('num')->filter(fn ($n) => $n !== null);

        if ($nums->count() < 2) {
            return $values;
        }

        $min = (float) $nums->min();
        $max = (float) $nums->max();

        if ($max <= $min) {
            return $values;
        }

        // ~6 ჯგუფი, მრგვალ საფეხურზე დაყრდნობით (1/2/5 × 10^n)
        $rawStep   = ($max - $min) / 6;
        $magnitude = 10 ** floor(log10(max($rawStep, 0.0001)));
        $step      = collect([1, 2, 5, 10])
            ->map(fn ($m) => $m * $magnitude)
            ->first(fn ($candidate) => $candidate >= $rawStep) ?? $magnitude * 10;

        $label = fn ($from, $to) => rtrim(rtrim(number_format($from, 2, '.', ''), '0'), '.')
            . '-' . rtrim(rtrim(number_format($to, 2, '.', ''), '0'), '.')
            . ($unit ? ' ' . SpecValue::displayUnit($unit) : '');

        $buckets = [];
        foreach ($values as $v) {
            if ($v['num'] === null) {
                $buckets['__other'] ??= ['label' => $v['label'], 'raw' => [], 'num' => null];
                $buckets['__other']['raw'] = array_merge($buckets['__other']['raw'], $v['raw']);
                continue;
            }

            $from = floor($v['num'] / $step) * $step;
            $to   = $from + $step;
            $key  = (string) $from;

            $buckets[$key] ??= ['label' => $label($from, $to), 'raw' => [], 'num' => $from];
            $buckets[$key]['raw'] = array_merge($buckets[$key]['raw'], $v['raw']);
        }

        return collect($buckets)
            ->sortBy(fn ($b) => $b['num'] ?? PHP_INT_MAX)
            ->values();
    }

    private function buildProductQuery()
    {
        return Product::query()
            ->select('db_products.id', 'db_products.category_id', 'db_products.brand_id', 'db_products.show', 'db_products.active', 'db_products.main_image', 'db_products.sku')
            ->with([
                'translations' => fn ($q) => $q
                    ->select('id', 'product_id', 'title', 'slug', 'locale')
                    ->where('locale', app()->getLocale()),
                'price' => fn ($q) => $q
                    ->select('id', 'product_id', 'regular_price', 'discount_price'),
                'brand' => fn ($q) => $q
                    ->select('id', 'logo', 'active'),
            ])
            ->where('db_products.show', 1)
            ->where('db_products.active', 1)
            ->whereHas('price', fn ($q) => $q
                ->whereRaw('COALESCE(NULLIF(discount_price, 0), regular_price) > 0')
            )
            ->tap(fn ($q) => $this->applyAllFilters($q));
    }

    private function applyAllFilters($query): void
    {
        $this->applyCategoryFilter($query);
        $this->applyBrandFilter($query);
        $this->applyPriceFilter($query);
        $this->applySearchFilter($query);
        $this->applyDiscountFilter($query);
        $this->applySpecFilter($query);
    }

    private function applyCategoryFilter($query): void
    {
        if (empty($this->currentCategory)) return;

        if ($this->currentCategory->parent_id === 0) {
            $childIds = $this->currentCategory->children()
                ->whereHas('products', fn ($q) => $q->where('db_products.active', 1)->where('db_products.show', 1))
                ->pluck('id');
            $query->whereIn('db_products.category_id', $childIds);
        } else {
            $query->where('db_products.category_id', $this->currentCategory->id);
        }
    }

    private function applyBrandFilter($query): void
    {
        $brands = array_filter(array_map('intval', (array) $this->selectedBrands));
        if (empty($brands)) return;
        $query->whereIn('brand_id', $brands);
    }

    private function applyPriceFilter($query): void
    {
        if (empty($this->priceMin) && empty($this->priceMax)) return;

        $query->whereHas('price', function ($priceQuery) {
            if (!empty($this->priceMin)) {
                $priceQuery->whereRaw('COALESCE(NULLIF(discount_price, 0), regular_price) >= ?', [round((float) $this->priceMin, 2)]);
            }
            if (!empty($this->priceMax)) {
                $priceQuery->whereRaw('COALESCE(NULLIF(discount_price, 0), regular_price) <= ?', [round((float) $this->priceMax, 2)]);
            }
        });
    }

    private function applySearchFilter($query): void
    {
        if (empty($this->search)) return;

        $searchTerm = "%{$this->search}%";
        $query->where(function ($q) use ($searchTerm) {
            $q->where('db_products.id', 'like', $searchTerm)
                ->orWhereHas('translations', fn ($sub) => $sub
                    ->where('title', 'like', $searchTerm)
                    ->orWhere('description', 'like', $searchTerm)
                );
        });
    }

    private function applyDiscountFilter($query): void
    {
        if (!$this->onlyDiscounted) return;
        $query->whereHas('price', fn ($q) => $q->whereNotNull('discount_price')->where('discount_price', '>', 0));
    }

    private function applySpecFilter($query): void
    {
        $specs = array_filter((array) $this->selectedSpecs);
        if (empty($specs)) return;

        $sections = $this->specificationSections;

        // არჩეული კანონიკური მნიშვნელობები სპეციფიკაციების მიხედვით
        $wanted = [];
        foreach ($specs as $spec) {
            if (!str_contains($spec, '::')) continue;
            [$name, $label] = explode('::', $spec, 2);
            $wanted[$name][] = $label;
        }

        foreach ($wanted as $name => $labels) {
            $section = $sections[$name] ?? null;
            if (!$section) continue;

            // ბაზაში მნიშვნელობები ნედლად წევს, ამიტომ კანონიკურს ნედლებში ვთარგმნით:
            // „12000 BTU" → ['12000', '12000 BTU']
            $rawValues = collect($section['values'])
                ->whereIn('label', $labels)
                ->flatMap(fn ($v) => $v['raw'])
                ->unique()
                ->values()
                ->all();

            if (empty($rawValues)) continue;

            // ერთი სპეციფიკაციის შიგნით — OR (ან 12000, ან 18000)
            // სხვადასხვა სპეციფიკაციას შორის — AND (ცალკე whereHas)
            $query->whereHas('fullSpecifications', function ($q) use ($name, $rawValues) {
                $q->whereHas('list', function ($item) use ($name, $rawValues) {
                    $item->where('name', $name)->whereIn('value', $rawValues);
                });
            });
        }
    }

    public function render()
    {
        $categorySections = collect();
        if ($this->currentCategory) {
            $categorySections = ProductSection::query()
                ->where('active', 1)
                ->where('category_id', $this->currentCategory->id)
                ->get();
        }

        return view('livewire.web.product.index', [
            'products'              => $this->products,
            'brands'                => $this->brands,
            'specificationSections' => $this->specificationSections,
            'isLoading'             => $this->isLoading,
            'event_id'              => $this->eventId,
            'categorySections'      => $categorySections,
        ])->layout('livewire.web.layout');
    }
}