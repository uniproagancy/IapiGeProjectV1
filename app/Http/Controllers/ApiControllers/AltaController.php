<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\AltaID;
use App\Models\Product\Product;
use App\Services\Products\AltaService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SoapClient;
use Exception;

class AltaController extends Controller
{
    private ?SoapClient $soapClient = null;
    private string $wsdlUrl = 'http://extra.alta.com.ge/b2b/b2bEWS?WSDL';

    private function getSoapClient(): SoapClient
    {
        if (!$this->soapClient) {
            $this->soapClient = new SoapClient($this->wsdlUrl, [
                'trace'      => 1,
                'exceptions' => true,
                'encoding'   => 'UTF-8',
            ]);
        }
        return $this->soapClient;
    }

    // ============================================
    // B2B Sync
    // ============================================

    public function getProducts()
    {
        try {
            $params = ['user' => 'UNIPRO_CHI', 'password' => 'CHI1457160', 'item' => ''];
            $response = $this->getSoapClient()->GetPriceList($params);

            AltaID::truncate();
            Product::where('sku', 'LIKE', '%ALTA-%')->update(['show' => 0]);

            $items = $response->PriceList->items->item;
            if (!is_array($items)) $items = [$items];

            $altaIds = [];
            foreach ($items as $item) {
                $parsed   = $this->parseQtyText($item->qty_text ?? null);
                $quantity = $parsed['quantity'];
                $show     = $quantity > 2 ? 1 : 0;
                $altaIds[] = ['product_id' => (string) $item->item, 'quantity' => $quantity];
                Product::where('sku', 'ALTA-' . $item->item)->update(['show' => $show, 'quantity' => $quantity, 'in_stock' => $show, 'active' => $show]);
            }

            if (!empty($altaIds)) {
                AltaID::insert($altaIds);
                Log::info('✅ AltaID updated: ' . count($altaIds) . ' items');
            }
        } catch (Exception $e) {
            Log::error('Alta getProducts error: ' . $e->getMessage());
            throw new Exception('ფასების მიღება ვერ მოხერხდა: ' . $e->getMessage());
        }
    }

    // ============================================
    // Scan
    // ============================================

    public function scan()
    {
        try {
            Log::info('AltaController->scan');
            $service = new AltaService();
            $stats   = $service->scanAllIds();
            return response()->json($stats);
        } catch (Exception $e) {
            Log::error('Alta scan error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ============================================
    // Debug — GET /alta/debug?count=5
    // პირველ N პროდუქტს სინქრონულად ამუშავებს
    // ============================================

    public function debug(Request $request)
    {
        $count = min((int) $request->get('count', 5), 20);

        Log::info("🔍 Alta Debug: დაიწყო | count={$count}");

        $token = env('ALTA_ACCESS_TOKEN');
        if (empty($token)) {
            return response()->json(['error' => 'ALTA_ACCESS_TOKEN არ არის .env-ში'], 500, [], JSON_UNESCAPED_UNICODE);
        }

        $altaIdCount = AltaID::count();
        if ($altaIdCount === 0) {
            return response()->json(['error' => 'AltaID ცხრილი ცარიელია — გაუშვი /alta/products ჯერ'], 500, [], JSON_UNESCAPED_UNICODE);
        }

        $results  = [];
        $apiUrl   = 'https://alta.ge/api/proxy/v1/Products/details?productId=';
        $tested   = 0;
        $found    = 0;
        $notFound = 0;
        $errors   = 0;

        // AltaID-ებიდან პირველი $count * 50 ვინახავთ და ვფილტრავთ
        $altaIds = range(1, 100000);
        $client  = new Client([
            'timeout'         => 15,
            'connect_timeout' => 10,
            'http_errors'     => false,
            'verify'          => false,
            'headers'         => [
                'Accept'              => 'application/json, text/plain, */*',
                'Accept-Language'     => 'ka',
                'Referer'             => 'https://alta.ge/produqtebi',
                'User-Agent'          => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
                'os'                  => 'web',
                'sec-ch-ua'           => '"Google Chrome";v="149", "Chromium";v="149", "Not)A;Brand";v="24"',
                'sec-ch-ua-mobile'    => '?0',
                'sec-ch-ua-platform'  => '"Windows"',
            ],
        ]);

        // გავიაროთ პირველი 500 ID — ვიპოვოთ count რაოდენობა
        for ($id = 1; $id <= 500 && $found < $count; $id++) {
            try {
                $response = $client->get($apiUrl . $id);
                $status   = $response->getStatusCode();

                if ($status === 401 || $status === 403) {
                    return response()->json([
                        'error'  => "Token invalid! status={$status}",
                        'tested' => $tested,
                    ], 401, [], JSON_UNESCAPED_UNICODE);
                }

                if ($status !== 200) {
                    $errors++;
                    continue;
                }

                $tested++;
                $data    = json_decode($response->getBody()->getContents(), true);
                $product = $data['product'] ?? null;

                if (!$product) {
                    $notFound++;
                    continue;
                }

                $barCode = $product['barCode'] ?? null;
                $inAltaIds = $barCode && AltaID::where('product_id', (string) $barCode)->exists();

                $results[] = [
                    'alta_id'      => $id,
                    'name'         => $product['name'] ?? '—',
                    'barCode'      => $barCode,
                    'in_alta_ids'  => $inAltaIds,
                    'would_queue'  => $inAltaIds,
                    'price'        => $product['price'] ?? null,
                    'category'     => $product['categoryName'] ?? null,
                ];

                if ($inAltaIds) $found++;

            } catch (Exception $e) {
                $errors++;
                Log::warning("Alta debug error id={$id}: " . $e->getMessage());
            }
        }

        Log::info("🔍 Alta Debug: დასრულდა | tested={$tested} found={$found} notFound={$notFound} errors={$errors}");

        return response()->json([
            'summary' => [
                'tested'      => $tested,
                'found'       => $found,
                'not_found'   => $notFound,
                'errors'      => $errors,
                'alta_id_count' => $altaIdCount,
            ],
            'products' => $results,
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    // ============================================
    // B2B Check
    // ============================================

    public function check(Request $request)
    {
        $sku = trim($request->get('sku', ''));
        if (empty($sku)) return response()->json(['error' => 'sku პარამეტრი საჭიროა'], 400);

        try {
            $params   = ['user' => 'UNIPRO_CHI', 'password' => 'CHI1457160', 'item' => $sku];
            $response = $this->getSoapClient()->GetPriceList($params);
            return response()->json($response);
        } catch (Exception $e) {
            Log::error('Alta check error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ============================================
    // Helpers
    // ============================================

    private function parseQtyText(?string $qtyText): array
    {
        try {
            if (empty($qtyText)) return ['has_stock' => false, 'quantity' => 0];
            $qtyText = strtolower(trim($qtyText));
            if (strpos($qtyText, '>=') === 0) { $minQty = (int) str_replace('>=', '', $qtyText); return ['has_stock' => true, 'quantity' => max($minQty, 10)]; }
            if (strpos($qtyText, '>') === 0) { $qty = (int) preg_replace('/[^0-9]/', '', $qtyText); return ['has_stock' => $qty > 0, 'quantity' => $qty]; }
            if (is_numeric($qtyText)) { $qty = (int) $qtyText; return ['has_stock' => $qty > 0, 'quantity' => $qty]; }
            if (preg_match('/out|not|unavailable|უ/i', $qtyText)) return ['has_stock' => false, 'quantity' => 0];
            if (preg_match('/in stock|available|ხელმ|აქვ/i', $qtyText)) return ['has_stock' => true, 'quantity' => 5];
            return ['has_stock' => true, 'quantity' => 5];
        } catch (Exception $e) {
            return ['has_stock' => false, 'quantity' => 0];
        }
    }
}