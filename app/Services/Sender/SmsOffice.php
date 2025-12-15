<?php


namespace App\Services\Sender;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class SmsOffice
{
    protected string $url;
    protected string $api_key;
    protected string $sender;

    public function __construct()
    {
        $this->url = Config::get('smsoffice.url');
        $this->api_key = Config::get('smsoffice.api_key');
        $this->sender = Config::get('smsoffice.sender');
    }

    public function send(string $phoneNumber, string $text): bool
    {
        try {
            $response = Http::asForm()->get($this->url, [
                'key' => $this->api_key,
                'destination' => $phoneNumber,
                'sender' => $this->sender,
                'content' => $text,
            ]);
            if ($response->successful()) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

}