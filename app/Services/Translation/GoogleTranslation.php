<?php

namespace App\Services\Translation;

use Google\Cloud\Translate\V3\Client\TranslationServiceClient;
use Google\Cloud\Translate\V3\TranslateTextRequest;
use Illuminate\Support\Facades\Cache;

class GoogleTranslation
{
    protected $translateClient;
    protected $projectId;

    public function __construct()
    {
        $keyFile = storage_path('app/google.json');
        if (!file_exists($keyFile)) {
            throw new \Exception("Google credentials file not found: {$keyFile}");
        }
        $this->translateClient = new TranslationServiceClient([
            'credentials' => $keyFile,
        ]);
        $this->projectId = 'shaped-kite-480212-m4';
    }

    public function translateToGeorgian($text)
    {
        if (empty($text)) {
            return $text;
        }
        $cacheKey = 'translation_' . md5($text) . '_ka';
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        try {
            $request = new TranslateTextRequest([
                'parent' => "projects/{$this->projectId}",
                'target_language_code' => 'ka',
                'contents' => [$text],
                'source_language_code' => 'en',
            ]);
            $response = $this->translateClient->translateText($request);
            $translations = $response->getTranslations();
            $translatedText = $translations[0]->getTranslatedText() ?? $text;
            Cache::put($cacheKey, $translatedText, now()->addDays(30));
            return $translatedText;
        } catch (\Exception $e) {
            return $text;
        }
    }

    public function translateToRussian($text)
    {
        if (empty($text)) {
            return $text;
        }
        $cacheKey = 'translation_' . md5($text) . '_ru';
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        try {
            $request = new TranslateTextRequest([
                'parent' => "projects/{$this->projectId}",
                'target_language_code' => 'ru',
                'contents' => [$text],
                'source_language_code' => 'en',
            ]);
            $response = $this->translateClient->translateText($request);
            $translations = $response->getTranslations();
            $translatedText = $translations[0]->getTranslatedText() ?? $text;
            Cache::put($cacheKey, $translatedText, now()->addDays(30));
            return $translatedText;
        } catch (\Exception $e) {
            return $text;
        }
    }
}