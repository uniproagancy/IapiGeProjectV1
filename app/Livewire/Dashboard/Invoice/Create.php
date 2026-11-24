<?php

namespace App\Livewire\Dashboard\Invoice;

use App\Models\Invoice\Invoice;
use App\Models\Product\Product;
use App\Services\Invoice\InvoicePdf;
use Livewire\Component;

class Create extends Component
{
    public string $customer_name    = '';
    public string $customer_code    = '';
    public string $customer_phone   = '';
    public string $customer_email   = '';
    public string $customer_address = '';
    public string $delivery_amount  = '0';
    public string $note             = '';

    /** @var array<int, array{product_id:?int, name:string, quantity:string, price:string, image:?string}> */
    public array $items = [];

    public string $productSearch = '';

    public function mount(): void
    {
        $this->addItem();
    }

    // ============================================
    // პოზიციები
    // ============================================

    public function addItem(): void
    {
        $this->items[] = ['product_id' => null, 'name' => '', 'quantity' => '1', 'price' => '', 'image' => null];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);

        if (empty($this->items)) {
            $this->addItem();
        }
    }

    /**
     * კატალოგიდან დამატება — სახელი და მიმდინარე ფასი ავტომატურად ივსება,
     * მაგრამ ორივე რედაქტირებადი რჩება (ფასდაკლება, სხვა ფასი კორპორატიულ კლიენტზე).
     */
    public function addProduct(int $productId): void
    {
        $product = Product::with(['translations', 'price'])->find($productId);

        if (!$product) return;

        $price = (float) ($product->price?->discount_price ?: $product->price?->regular_price ?: 0);

        $row = [
            'product_id' => $product->id,
            'name'       => $product->translation('ka')?->title ?? ('პროდუქტი #' . $product->id),
            'quantity'   => '1',
            'price'      => (string) $price,
            'image'      => $product->main_image,
        ];

        // ბოლო ცარიელ რიგს ვავსებთ, ახალს არ ვამატებთ
        $last = array_key_last($this->items);
        if ($last !== null && trim($this->items[$last]['name']) === '' && trim((string) $this->items[$last]['price']) === '') {
            $this->items[$last] = $row;
        } else {
            $this->items[] = $row;
        }

        $this->productSearch = '';
    }

    public function getSearchResultsProperty()
    {
        $term = trim($this->productSearch);

        if (mb_strlen($term) < 2) {
            return collect();
        }

        return Product::query()
            ->with(['translations', 'price'])
            ->where(function ($q) use ($term) {
                $q->where('sku', 'like', "%{$term}%")
                    ->orWhere('id', $term)
                    ->orWhereHas('translations', fn ($t) => $t->where('title', 'like', "%{$term}%"));
            })
            ->orderByDesc('active')
            ->limit(8)
            ->get();
    }

    public function getSubtotalProperty(): float
    {
        return collect($this->items)->sum(fn ($i) => $this->toFloat($i['quantity']) * $this->toFloat($i['price']));
    }

    public function getTotalProperty(): float
    {
        return $this->subtotal + $this->toFloat($this->delivery_amount);
    }

    // ============================================
    // შენახვა
    // ============================================

    public function save()
    {
        // ცარიელი რიგები ფორმაში რჩება, მაგრამ ინვოისში არ გადადის
        $this->items = array_values(array_filter(
            $this->items,
            fn ($i) => trim($i['name']) !== '' || trim((string) $i['price']) !== ''
        ));

        if (empty($this->items)) {
            $this->addItem();
        }

        // „12,50" → „12.50" ვალიდაციამდე, თორემ numeric წესი უარყოფს
        $normalize = fn ($v) => str_replace([',', ' '], ['.', ''], trim((string) $v));
        foreach ($this->items as $k => $item) {
            $this->items[$k]['quantity'] = $normalize($item['quantity']);
            $this->items[$k]['price']    = $normalize($item['price']);
        }
        $this->delivery_amount = $normalize($this->delivery_amount) ?: '0';

        $this->validate([
            'customer_name'      => 'required|string|max:255',
            'customer_code'      => 'nullable|string|max:64',
            'customer_phone'     => 'nullable|string|max:64',
            'customer_email'     => 'nullable|email|max:255',
            'customer_address'   => 'nullable|string|max:500',
            'delivery_amount'    => 'nullable|numeric|min:0',
            'note'               => 'nullable|string|max:2000',
            'items'              => 'required|array|min:1',
            'items.*.name'       => 'required|string|max:500',
            'items.*.quantity'   => 'required|numeric|min:0.01',
            'items.*.price'      => 'required|numeric|min:0',
        ], [
            'customer_name.required'  => 'მომხმარებლის სახელი აუცილებელია',
            'customer_email.email'    => 'ელ-ფოსტა არასწორია',
            'items.*.name.required'   => 'დასახელება აუცილებელია',
            'items.*.quantity.*'      => 'რაოდენობა არასწორია',
            'items.*.price.required'  => 'ფასი აუცილებელია',
            'items.*.price.numeric'   => 'ფასი რიცხვი უნდა იყოს',
            'delivery_amount.numeric' => 'მიტანის ფასი რიცხვი უნდა იყოს',
        ]);

        $items = collect($this->items)->map(fn ($i) => [
            'product_id' => $i['product_id'] ?: null,
            'name'       => trim($i['name']),
            'quantity'   => round($this->toFloat($i['quantity']), 2),
            'price'      => round($this->toFloat($i['price']), 2),
            'image'      => $i['image'] ?: null,
        ])->all();

        $invoice = Invoice::create([
            'customer_name'    => trim($this->customer_name),
            'customer_code'    => trim($this->customer_code) ?: null,
            'customer_phone'   => trim($this->customer_phone) ?: null,
            'customer_email'   => trim($this->customer_email) ?: null,
            'customer_address' => trim($this->customer_address) ?: null,
            'items'            => $items,
            'delivery_amount'  => round($this->toFloat($this->delivery_amount), 2),
            'total'            => round($this->total, 2),
            'note'             => trim($this->note) ?: null,
            'created_by'       => auth()->id(),
        ])->refresh();

        session()->flash('invoice_created', $invoice->number);

        // ✅ PDF მაშინვე ჩამოიტვირთება; სიაში ხელახლა ჩამოტვირთვაც შეიძლება
        $pdf = InvoicePdf::render(InvoicePdf::fromInvoice($invoice))->output();

        $this->reset(['customer_name', 'customer_code', 'customer_phone', 'customer_email', 'customer_address', 'note', 'productSearch']);
        $this->delivery_amount = '0';
        $this->items = [];
        $this->addItem();

        $this->dispatch('ui:success', message: "ინვოისი {$invoice->number} შეიქმნა");

        return response()->streamDownload(fn () => print($pdf), $invoice->number . '.pdf');
    }

    /** „12,50" და „12.50" ორივე უნდა იმუშაოს */
    private function toFloat($value): float
    {
        return (float) str_replace([',', ' '], ['.', ''], (string) $value);
    }

    public function render()
    {
        return view('livewire.dashboard.invoice.create')
            ->layout('livewire.dashboard.layout');
    }
}
