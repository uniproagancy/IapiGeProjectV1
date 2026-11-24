<?php

namespace App\Services\Catalog;

use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Support\SpecValue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * კატეგორიისთვის სპეციფიკაციების შემოთავაზება — რომელი ღირს ფილტრად.
 *
 * ადმინი 521 სახელიდან ხელით ვერ შეარჩევს, ამიტომ მონაცემებიდან ვთვლით:
 * რამდენ პროდუქტზეა და რამდენი გამოსადეგი ვარიანტი აქვს ნორმალიზაციის შემდეგ.
 */
class CategoryFilterSuggester
{
    /** სახელები, რომლებიც ფილტრად არასდროს გამოდგება */
    private const BLOCKED = [
        'model/part number', 'მოდელი', 'sku', 'ean', 'part number',
        'ბრენდი', 'brand', 'აღწერა', 'description', 'გარანტია',
    ];

    /**
     * კატეგორიის პროდუქტების ID-ები. მშობელ კატეგორიაზე შვილებიდან ვკრებთ.
     */
    public function productIds(ProductCategory $category): Collection
    {
        return Product::query()
            ->where('show', 1)
            ->where('active', 1)
            ->when(
                (int) $category->parent_id === 0,
                fn ($q) => $q->whereIn('category_id', $category->children()->pluck('id')),
                fn ($q) => $q->where('category_id', $category->id)
            )
            ->pluck('id');
    }

    /**
     * შემოთავაზებები, სარგებლიანობით დალაგებული.
     *
     * @return Collection<int, array{spec_name:string, products:int, coverage:int, values:int, unit:?string, sample:string}>
     */
    public function suggest(ProductCategory $category): Collection
    {
        $productIds = $this->productIds($category);

        if ($productIds->isEmpty()) {
            return collect();
        }

        $total = $productIds->count();

        $rows = collect(
            DB::table('db_product_full_specification_items as i')
                ->join('db_product_full_specification_sections as s', 's.id', '=', 'i.section_id')
                ->whereIn('s.product_id', $productIds)
                ->whereNull('i.deleted_at')
                ->whereNotNull('i.value')
                ->where('i.value', '!=', '')
                ->select('i.name', 'i.value', 's.product_id')
                ->get()
        );

        return $rows
            ->groupBy('name')
            ->map(function ($group, $name) use ($total) {
                $products = $group->pluck('product_id')->unique()->count();
                $raw      = $group->pluck('value')->map(fn ($v) => (string) $v)->unique();
                $unit     = SpecValue::dominantUnit($raw);
                $labels   = $raw->map(fn ($v) => SpecValue::canonical($v, $unit))->unique()->values();

                return [
                    'spec_name' => $name,
                    'products'  => $products,
                    'coverage'  => $total > 0 ? (int) round($products / $total * 100) : 0,
                    'values'    => $labels->count(),
                    'unit'      => $unit ? SpecValue::displayUnit($unit) : null,
                    'sample'    => $labels->take(3)->implode(' · '),
                ];
            })
            ->reject(fn ($s) => in_array(mb_strtolower($s['spec_name']), self::BLOCKED, true))
            // ერთვარიანტიანი ფილტრი უაზროა; 60-ზე მეტი — ვერ გამოიყენება
            ->filter(fn ($s) => $s['values'] >= 2 && $s['values'] <= 60)
            ->sortByDesc(fn ($s) => $s['coverage'] * 100 + min($s['values'], 30))
            ->values();
    }
}
