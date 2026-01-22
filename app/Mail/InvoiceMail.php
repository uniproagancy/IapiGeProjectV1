<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $invoice;
    public $data;

    public function __construct(Order $order, $invoice, $data)
    {
        $this->order = $order;
        $this->invoice = $invoice;
        $this->data = $data;
    }

    public function build()
    {
        return $this->subject("ინვოისი #{$this->order->id}")
            ->view('livewire.dashboard.invoices.order', $this->data)
            ->attachData($this->invoice, "invoice-{$this->order->id}.pdf", [
                'mime' => 'application/pdf',
            ]);
    }
}
