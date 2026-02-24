<?php

namespace App\Livewire\Dashboard\Order;

use App\Models\Delivery\DeliveryCompany;
use App\Models\Order\Order;
use App\Models\Order\OrderDelivery;
use App\Models\Order\OrderStatus;
use App\Models\Payments\PaymentStatus;
use Livewire\Component;

class View extends Component
{

    public $order_id;
    public $order;
    public $status_id;
    public $payment_status_id;

    public $delivery_company_id;

    protected $listeners = [
        'order-refresh' => '$refresh',
        'cancelDeliveryModal',
        'cancelDelivery ',
    ];

    public function mount()
    {
        $this->order = Order::with('orderStatus', 'paymentStatus')->findOrFail($this->order_id);
        if ($this->order->status_id === 1) {
            $this->order->update(['status_id' => 2]);
            $this->order->refresh();
        }
        $this->status_id = $this->order->status_id;
        $this->payment_status_id = $this->order->payment_status_id;
    }

    public function updateOrderStatus()
    {
        $this->validate([
            'status_id'         => 'required|integer',
            'payment_status_id' => 'required|integer',
        ]);

        $previousPaymentStatus = $this->order->payment_status_id;

        $this->order->update([
            'status_id'         => $this->status_id,
            'payment_status_id' => $this->payment_status_id,
        ]);

        // ✅ მხოლოდ მაშინ გაიგზავნოს როდესაც სტატუსი იცვლება 2-ზე
        if ($this->payment_status_id === 2 && $previousPaymentStatus !== 2) {
            $this->trackPurchase($this->order->fresh());
        }

        $this->dispatch('ui:success', message: 'შეკვეთის სტატუსი წარმატებით განახლდა!', title: 'შეტყობინება');
        $this->dispatch('status_modal_close');
    }

    private function trackPurchase(\App\Models\Order\Order $order): void
    {
        try {
            $eventId    = 'purchase_' . time() . '_' . \Illuminate\Support\Str::random(6);
            $contents   = [];
            $contentIds = [];

            foreach ($order->items as $item) {
                $contents[]   = [
                    'id'         => $item->product_id,
                    'quantity'   => $item->quantity,
                    'item_price' => $item->price,
                ];
                $contentIds[] = $item->product_id;
            }

            app(\App\Services\Facebook\FacebookPixelService::class)->trackPurchaseWithTest(
                testCode: 'TEST8409',
                value: $order->amount,
                currency: 'GEL',
                params: [
                    'contents'     => $contents,
                    'content_ids'  => $contentIds,
                    'content_type' => 'product',
                    'num_items'    => count($contents),
                ],
                eventId: $eventId
            );

            \Illuminate\Support\Facades\Log::info('✅ Purchase tracked', [
                'order_id' => $order->id,
                'event_id' => $eventId,
                'amount'   => $order->amount,
                'items'    => count($contents),
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('❌ Purchase pixel error: ' . $e->getMessage(), [
                'order_id' => $order->id ?? null,
            ]);
        }
    }

    public function sendToDeliveryCompany()
    {
        $this->validate([
            'delivery_company_id' => 'required|integer|exists:db_delivery_companies,id'
        ], [
            'required' => 'გთხოვთ აირჩიოთ საკურიერო კომპანია!',
            'exists' => 'საკურიერო კომპანია ვერ მოიძებნა!',
        ]);
        if ($this->order->payment_status_id != 2) {
            $this->dispatch('ui:error', message: 'შეკვეთა არ არის გადახდილი!', title: 'შეტყობინება');
            return;
        }
        if ($this->order->status_id === 4) {
            $this->dispatch('ui:error', message: 'შეკვეთა გაუქმებულია!', title: 'შეტყობინება');
            return;
        }
        OrderDelivery::create([
            'order_id' => $this->order_id,
            'delivery_id' => $this->delivery_company_id,
        ]);
        // TODO API
        $this->dispatch('ui:success', message: 'შეკვეთა გადაეგზავნა საკურიეროს!', title: 'შეტყობინება');
        $this->dispatch('delivery_company_modal_close');
    }

    public function cancelDeliveryModal()
    {
        $this->dispatch('swal:cancelDeliveryModal', [
            'title' => 'მიწოდების გაუქმება?',
            'icon' => 'warning',
            'confirmButtonText' => 'გაუქმება!',
            'cancelButtonText' => 'დახურვა!',
            'type' => 'restore'
        ]);
    }

    public function cancelDelivery()
    {
        OrderDelivery::where('order_id', $this->order_id)->forceDelete();
        $this->dispatch('ui:success', message: 'შეკვეთის გადაგზავნა შეჩერებულია!', title: 'შეტყობინება');
    }

    public function downloadInvoice()
    {
        return redirect()->route('dashboard.order.invoice.download', $this->order_id);
    }

    public function sendInvoice()
    {
        return redirect()->route('dashboard.order.invoice.send', $this->order_id);
    }

    public function render()
    {
        return view('livewire.dashboard.order.view', [
            'order' => $this->order,
            'order_statuses' => OrderStatus::all(),
            'payment_statuses' => PaymentStatus::all(),
            'delivery_companies' => DeliveryCompany::where('active', 1)->get(),
        ])->layout('livewire.dashboard.layout');
    }
}
