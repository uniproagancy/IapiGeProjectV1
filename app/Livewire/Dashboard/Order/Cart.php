<?php

namespace App\Livewire\Dashboard\Order;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Darryldecode\Cart\Facades\CartFacade as LaravelCart;

class Cart extends Component
{
    public $items;
    public $total;

    protected $listeners = ['addToCart' => 'add'];

    public function mount()
    {
        $this->syncCartFromDatabase();
        $this->refreshCart();
    }

    protected function syncCartFromDatabase()
    {
        if (Auth::check()) {
            $dbItems = CartItem::where('user_id', Auth::id())->get();
            foreach ($dbItems as $item) {
                LaravelCart::updateOrAdd($item->product_id, [
                    'id' => $item->product_id,
                    'name' => $item->product->name,
                    'price' => $item->price ?? $item->product->price,
                    'quantity' => $item->quantity,
                    'attributes' => [
                        'image' => $item->product->image ?? null,
                        'sku' => $item->product->sku ?? null,
                    ],
                ]);
            }
        }
    }

    public function refreshCart()
    {
        $this->items = LaravelCart::getContent();
        $this->total = LaravelCart::getTotal();
    }

    public function add($productId)
    {
        $product = Product::find($productId);

        if (!$product) {
            $this->dispatch('error', message: 'პროდუქტი ვერ მოიძებნა');
            return;
        }
        $existing = LaravelCart::get($product->id);
        $currentQty = $existing ? $existing->quantity : 0;
        if ($currentQty >= $product->stock) {
            $this->dispatch('error', message: 'მოცემული რაოდენობა აღარაა საწყობში');
            return;
        }
        if ($existing) {
            LaravelCart::update($product->id, [
                'quantity' => [
                    'relative' => true,
                    'value' => 1,
                ],
            ]);
        } else {
            LaravelCart::add([
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
                'attributes' => [
                    'image' => $product->image ?? null,
                    'sku' => $product->sku ?? null,
                ],
            ]);
        }
        if (Auth::check()) {
            $cartItem = CartItem::firstOrNew([
                'user_id' => Auth::id(),
                'product_id' => $product->id,
            ]);

            $cartItem->quantity = $existing ? $cartItem->quantity + 1 : 1;
            $cartItem->price = $product->price;
            $cartItem->save();
        }
        $this->refreshCart();
        $this->dispatch('success', message: 'პროდუქტი დაემატა კალათაში!');
    }

    public function updateQuantity($id, $quantity)
    {
        $product = Product::find($id);
        if (!$product) {
            $this->dispatch('error', message: 'პროდუქტი ვერ მოიძებნა');
            return;
        }
        if ($quantity > $product->stock) {
            $this->dispatch('error', message: 'არ არის საკმარისი ნაშთი');
            return;
        }
        LaravelCart::update($id, [
            'quantity' => [
                'relative' => false,
                'value' => $quantity,
            ],
        ]);
        if (auth()->check()) {
            CartItem::where('user_id', auth()->id())
                ->where('product_id', $id)
                ->update(['quantity' => $quantity]);
        }
        $this->dispatch('success', message: 'რაოდენობა განახლდა');
        $this->refreshCart();
    }

    public function remove($id)
    {
        LaravelCart::remove($id);
        if (Auth::check()) {
            CartItem::where('user_id', Auth::id())
                ->where('product_id', $id)
                ->delete();
        }
        $this->refreshCart();
    }

    public function clear()
    {
        LaravelCart::clear();
        if (Auth::check()) {
            CartItem::where('user_id', Auth::id())->delete();
        }
        $this->refreshCart();
    }

    public function render()
    {
        return view('livewire.dashboard.order.cart');
    }
}
