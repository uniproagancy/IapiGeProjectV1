<?php

namespace App\Http\Controllers;

use App\Models\Product\Product;
use Illuminate\Http\Request;

class OrderStatusController extends Controller
{
    public function success() {
        return view('livewire.web.payment-success');
    }

    public function reject() {
        return view('livewire.web.payment-reject');
    }
}
