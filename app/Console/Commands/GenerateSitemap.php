<?php

namespace App\Console\Commands;

use App\Models\Content\StaticPage;
use App\Models\Product\Product;
use App\Models\Product\ProductCategory;
use App\Models\Product\ProductSection;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate sitemap.xml for iapi.ge';

    public function handle(): int
    {
        $this->info('Generating sitemap...');

        $sitemap = Sitemap::create();

        $sitemap->add(
            Url::create(route('web.main.index'))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(1.0)
        );

        $sitemap->add(
            Url::create(route('web.main.contact'))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.6)
        );

        $sitemap->add(
            Url::create(route('web.main.about'))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.6)
        );

        $sitemap->add(
            Url::create(route('web.promotions.index'))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.7)
        );

        $sitemap->add(
            Url::create(route('web.products.index'))
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.8)
        );

        $categories = ProductCategory::whereNull('deleted_at')
            ->where('active', 1)
            ->get();

        foreach ($categories as $category) {
            $slug = $category->translation('ka')?->slug;
            if (!$slug) continue;

            $sitemap->add(
                Url::create(route('web.products.index', $slug))
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.7)
                    ->setLastModificationDate($category->updated_at)
            );
        }

        Product::whereNull('deleted_at')
            ->where('active', 1)
            ->where('show', 1)
            ->select(['id', 'updated_at'])
            ->with(['translations' => fn($q) => $q->where('locale', 'ka')->select(['id', 'product_id', 'slug'])])
            ->chunk(500, function ($products) use ($sitemap) {
                foreach ($products as $product) {
                    $slug = $product->translations->first()?->slug;
                    if (!$slug) continue;

                    $sitemap->add(
                        Url::create(route('web.products.view', $slug))
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                            ->setPriority(0.8)
                            ->setLastModificationDate($product->updated_at)
                    );
                }
            });

        $sections = ProductSection::where('active', 1)->get();

        foreach ($sections as $section) {
            if (!$section->slug) continue;

            $sitemap->add(
                Url::create(route('web.section.view', $section->slug))
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority(0.6)
                    ->setLastModificationDate($section->updated_at)
            );
        }

        $staticPages = StaticPage::whereNull('deleted_at')
            ->where('locale', 'ka')
            ->get();

        foreach ($staticPages as $page) {
            if (!$page->url) continue;

            $sitemap->add(
                Url::create(route('web.static.index', $page->url))
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                    ->setPriority(0.5)
            );
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated: public/sitemap.xml');

        return self::SUCCESS;
    }
}
