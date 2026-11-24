<?php

namespace App\Http\Controllers;

use App\Models\Invoice\Invoice;
use App\Models\Order\Order;
use App\Services\Invoice\InvoicePdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * შეკვეთის ინვოისი.
     */
    public function invoiceDownload(Request $request)
    {
        $order = Order::findOrFail($request->order_id);

        return InvoicePdf::render(InvoicePdf::fromOrder($order))
            ->download('order_invoice_' . $order->id . '.pdf');
    }

    public function invoiceSend(Request $request)
    {
        $order   = Order::findOrFail($request->order_id);
        $invoice = InvoicePdf::render(InvoicePdf::fromOrder($order))->output();
//        Mail::to($order->user->email)->send(new InvoiceMail($order, $invoice, $data));
    }

    /**
     * შეკვეთის გარეშე გაცემული ინვოისი.
     */
    public function download(int $id)
    {
        $invoice = Invoice::findOrFail($id);

        return InvoicePdf::render(InvoicePdf::fromInvoice($invoice))
            ->download($invoice->number . '.pdf');
    }
}
