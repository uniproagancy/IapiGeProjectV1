<?php

namespace App\Services\Payments;

use App\Models\Order\OrderTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TBCInstallment
{
    private string $clientId;
    private string $clientSecret;
    private string $orderUrl;
    private string $tokenUrl;
    private string $installmentSuccess;
    private string $installmentFail;
    private string $installmentReject;

    protected float $handlingPee;

    public function __construct()
    {
        $this->handlingPee = 0.05;
    }

    public function getToken()
    {
        $body = [
            'grant_type' => 'client_credentials',
            'scope' => 'online_installments'
        ];;
        $token = Http::withHeaders([
            'accept' => 'application/json',
            'client_id' => 'rpaBGYDgUP6qC07OxkJjxN3jf6SLcwsZ',
            'content-type' => 'application/json',
        ])->post('https://api.tbcbank.ge/oauth/token', $body);
        Log::info($token);
    }


}