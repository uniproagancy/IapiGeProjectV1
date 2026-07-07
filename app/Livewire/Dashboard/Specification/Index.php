<?php

namespace App\Livewire\Dashboard\Specification;

use App\Models\Product\ProductFullSpecificationItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search_query = '';
    public int $per_page        = 25;
    public string $filter_status = ''; // '', '1', '0'

    protected $queryString = [
        'search_query'  => ['except' => ''],
        'filter_status' => ['except' => ''],
        'per_page'      => ['except' => 25],
    ];

    public function paginationView(): string
    {
        return 'livewire.dashboard.partials._pagination';
    }

    public function updatedSearchQuery(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    // ============================================
    // Toggle filter for all items with given name
    // ============================================

    public function toggleFilter(string $name): void
    {
        try {
            // Check current dominant state
            $currentlyFilterable = ProductFullSpecificationItem::where('name', $name)
                ->whereNull('deleted_at')
                ->where('filter', 1)
                ->exists();

            $newValue = $currentlyFilterable ? 0 : 1;

            $updated = ProductFullSpecificationItem::where('name', $name)
                ->whereNull('deleted_at')
                ->update(['filter' => $newValue]);

            $status = $newValue ? 'ჩართულია' : 'გამორთულია';
            $this->dispatch('ui:success', message: "\"{$name}\" ფილტრი {$status} ({$updated} ჩანაწერი)");

        } catch (\Throwable $e) {
            Log::error('Spec filter toggle error', ['name' => $name, 'error' => $e->getMessage()]);
            $this->dispatch('ui:error', message: 'შეცდომა: ' . $e->getMessage());
        }
    }

    // ============================================
    // Bulk actions
    // ============================================

    public function enableAll(): void
    {
        $query = $this->buildBaseQuery();
        $names = $query->pluck('name');

        $updated = ProductFullSpecificationItem::whereIn('name', $names)
            ->whereNull('deleted_at')
            ->update(['filter' => 1]);

        $this->dispatch('ui:success', message: "ყველა ფილტრი ჩაირთო ({$updated} ჩანაწერი)");
    }

    public function disableAll(): void
    {
        $query = $this->buildBaseQuery();
        $names = $query->pluck('name');

        $updated = ProductFullSpecificationItem::whereIn('name', $names)
            ->whereNull('deleted_at')
            ->update(['filter' => 0]);

        $this->dispatch('ui:success', message: "ყველა ფილტრი გამოირთო ({$updated} ჩანაწერი)");
    }

    // ============================================
    // Helpers
    // ============================================

    private function buildBaseQuery()
    {
        return DB::table('db_product_full_specification_items')
            ->whereNull('deleted_at')
            ->when($this->search_query, fn($q) => $q->where('name', 'like', "%{$this->search_query}%"))
            ->when($this->filter_status !== '', function ($q) {
                $names = DB::table('db_product_full_specification_items')
                    ->whereNull('deleted_at')
                    ->where('filter', (int) $this->filter_status)
                    ->groupBy('name')
                    ->pluck('name');

                $q->whereIn('name', $names);
            })
            ->groupBy('name')
            ->select(
                'name',
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(CASE WHEN filter = 1 THEN 1 ELSE 0 END) as filter_count')
            )
            ->orderByDesc('total_count');
    }

    // ============================================
    // Render
    // ============================================

    public function render()
    {
        $query = $this->buildBaseQuery();

        // Manual pagination for grouped query
        $page    = $this->getPage();
        $total   = (clone $query)->get()->count();
        $items   = $query->offset(($page - 1) * $this->per_page)->limit($this->per_page)->get();

        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $this->per_page,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        // Stats
        $totalSpecs    = DB::table('db_product_full_specification_items')->whereNull('deleted_at')->count();
        $uniqueNames   = DB::table('db_product_full_specification_items')->whereNull('deleted_at')->distinct('name')->count('name');
        $filterEnabled = DB::table('db_product_full_specification_items')->whereNull('deleted_at')->where('filter', 1)->distinct('name')->count('name');

        return view('livewire.dashboard.specification.index', [
            'specifications' => $paginator,
            'totalSpecs'     => $totalSpecs,
            'uniqueNames'    => $uniqueNames,
            'filterEnabled'  => $filterEnabled,
        ])->layout('livewire.dashboard.layout');
    }
}