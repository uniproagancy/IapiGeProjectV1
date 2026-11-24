<?php

namespace App\Services\Invoice;

use App\Models\Invoice\Invoice;
use App\Models\Order\Order;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * ინვოისის PDF — ერთი შაბლონი შეკვეთისთვისაც და ხელით გაცემულისთვისაც.
 *
 * ორივე წყარო ერთ ფორმაში გადადის, ამიტომ შაბლონი არ იცის საიდან მოვიდა.
 * ყველა თანხა ლარშია.
 */
class InvoicePdf
{
    private const VIEW = 'livewire.dashboard.invoices.invoice';

    public static function fromInvoice(Invoice $invoice): array
    {
        return [
            'number'   => $invoice->number,
            'date'     => $invoice->created_at->format('d.m.Y'),
            'customer' => [
                'name'    => $invoice->customer_name,
                'code'    => $invoice->customer_code,
                'phone'   => $invoice->customer_phone,
                'email'   => $invoice->customer_email,
                'address' => $invoice->customer_address,
            ],
            'items' => collect($invoice->items)->map(fn ($i) => [
                'name'     => $i['name'] ?? '',
                'quantity' => (float) ($i['quantity'] ?? 1),
                'price'    => (float) ($i['price'] ?? 0),
                'image'    => self::imagePath($i['image'] ?? null),
            ])->all(),
            'delivery' => (float) $invoice->delivery_amount,
            'note'     => $invoice->note,
        ];
    }

    public static function fromOrder(Order $order): array
    {
        $order->loadMissing(['user', 'deliveryData', 'items.product.translations']);

        return [
            'number'   => (string) $order->id,
            'date'     => now()->format('d.m.Y'),
            'customer' => [
                'name'    => trim(($order->user->name ?? '') . ' ' . ($order->user->lastname ?? '')),
                'code'    => null,
                'phone'   => $order->user->phone ?? null,
                'email'   => $order->user->email ?? null,
                'address' => $order->deliveryData?->address ?: null,
            ],
            // ✅ შეკვეთის ფასი ბაზაში ლარშია (1099 = 1099 ₾).
            //    ძველი შაბლონი 100-ზე ყოფდა და ინვოისზე 10.99 იწერებოდა.
            'items' => $order->items->map(fn ($item) => [
                'name'     => $item->product?->translations->where('locale', 'ka')->first()?->title
                              ?? ('პროდუქტი #' . $item->product_id),
                'quantity' => (float) $item->quantity,
                'price'    => (float) $item->price,
                'image'    => self::imagePath($item->product?->main_image),
            ])->all(),
            'delivery' => (float) ($order->delivery_amount ?? 0),
            'note'     => null,
        ];
    }

    public static function render(array $data): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView(self::VIEW, $data);
    }

    /**
     * DomPDF ლოკალურ ფაილს ითხოვს; არარსებულ გზაზე გაფრთხილებას აგდებს და
     * ცარიელ ჩარჩოს ხატავს — ამიტომ მხოლოდ რეალურად არსებულს ვაბრუნებთ.
     */
    private static function imagePath(?string $relative): ?string
    {
        if (empty($relative)) {
            return null;
        }

        $path = public_path('storage/' . ltrim($relative, '/'));

        return is_file($path) ? $path : null;
    }
}
