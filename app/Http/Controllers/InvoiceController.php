<?php

namespace App\Http\Controllers;

use App\Mail\InvoiceMail;
use App\Models\Order\Order;

use Barryvdh\DomPDF\Facade\Pdf;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends Controller
{
    //
    public function invoiceDownload(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        $data = [
            'order_id' => $order->id,
            'user_name' => $order->user->name,
            'user_lastname' => $order->user->lastname,
            'user_email' => $order->user->email,
            'user_phone' => $order->user->phone,
            'products' => $order->items,
            'delivery_amount' => $order->delivery_amount
        ];
        $pdf = Pdf::loadView('livewire.dashboard.invoices.order', $data);
        return $pdf->download('order_invoice_'.$order->id.'.pdf');
    }

    public function invoiceSend(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        $data = [
            'order_id' => $order->id,
            'user_name' => $order->user->name,
            'user_lastname' => $order->user->lastname,
            'user_email' => $order->user->email,
            'user_phone' => $order->user->phone,
            'products' => $order->items,
            'delivery_amount' => $order->delivery_amount
        ];
        $invoice = Pdf::loadView('livewire.dashboard.invoices.order', $data)->output();
//        Mail::to($order->user->email)->send(new InvoiceMail($order, $invoice, $data));
    }
}
