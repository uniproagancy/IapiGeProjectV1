<?php

namespace App\Services\Products;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UshopProduct
{
    public function getProducts(): array
    {
        try {
            $products = DB::connection('ushop')
                ->select("
                    SELECT
                        p.ID,
                        p.post_title,
                        p.post_content,
                        sku.meta_value           AS sku,
                        stock.meta_value         AS stock_status,
                        regular_price.meta_value AS regular_price,
                        sale_price.meta_value    AS sale_price,
                        featured_img.meta_value  AS featured_image_path,
                        gallery.meta_value       AS gallery_ids,
                        GROUP_CONCAT(DISTINCT gallery_img.meta_value) AS gallery_images,
                        GROUP_CONCAT(DISTINCT cat.name)               AS categories,
                        GROUP_CONCAT(DISTINCT brand.name)             AS brand

                    FROM wp_posts p

                    LEFT JOIN wp_postmeta sku
                        ON p.ID = sku.post_id AND sku.meta_key = '_sku'
                    LEFT JOIN wp_postmeta stock
                        ON p.ID = stock.post_id AND stock.meta_key = '_stock_status'
                    LEFT JOIN wp_postmeta regular_price
                        ON p.ID = regular_price.post_id AND regular_price.meta_key = '_regular_price'
                    LEFT JOIN wp_postmeta sale_price
                        ON p.ID = sale_price.post_id AND sale_price.meta_key = '_sale_price'
                    LEFT JOIN wp_postmeta thumb
                        ON p.ID = thumb.post_id AND thumb.meta_key = '_thumbnail_id'
                    LEFT JOIN wp_postmeta featured_img
                        ON thumb.meta_value = featured_img.post_id
                        AND featured_img.meta_key = '_wp_attached_file'
                    LEFT JOIN wp_postmeta gallery
                        ON p.ID = gallery.post_id AND gallery.meta_key = '_product_image_gallery'
                    LEFT JOIN wp_postmeta gallery_img
                        ON FIND_IN_SET(gallery_img.post_id, gallery.meta_value)
                        AND gallery_img.meta_key = '_wp_attached_file'
                    LEFT JOIN wp_term_relationships tr_cat ON p.ID = tr_cat.object_id
                    LEFT JOIN wp_term_taxonomy tt_cat
                        ON tr_cat.term_taxonomy_id = tt_cat.term_taxonomy_id
                        AND tt_cat.taxonomy = 'product_cat'
                    LEFT JOIN wp_terms cat ON tt_cat.term_id = cat.term_id
                    LEFT JOIN wp_term_relationships tr_brand ON p.ID = tr_brand.object_id
                    LEFT JOIN wp_term_taxonomy tt_brand
                        ON tr_brand.term_taxonomy_id = tt_brand.term_taxonomy_id
                        AND tt_brand.taxonomy = 'product_brand'
                    LEFT JOIN wp_terms brand ON tt_brand.term_id = brand.term_id

                    WHERE p.post_type = 'product'
                      AND p.post_status = 'publish'

                    GROUP BY p.ID
                ");

            return array_map(fn($p) => (array) $p, $products);

        } catch (\Exception $e) {
            Log::error('UshopProduct DB error: ' . $e->getMessage());
            return [];
        }
    }
}