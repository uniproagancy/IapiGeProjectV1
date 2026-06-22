<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use App\Models\AltaID;
use App\Models\Product\Product;
use App\Services\Products\AltaService;
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
            try {
                $this->soapClient = new SoapClient($this->wsdlUrl, [
                    'trace'      => 1,
                    'exceptions' => true,
                    'encoding'   => 'UTF-8',
                ]);
            } catch (Exception $e) {
                throw new Exception('SOAP კლიენტის შეცდომა: ' . $e->getMessage());
            }
        }

        return $this->soapClient;
    }

    // ============================================
    // B2B Sync
    // ============================================

    public function getProducts()
    {
        try {
            $params = [
                'user'     => 'UNIPRO_CHI',
                'password' => 'CHI1457160',
                'item'     => '',
            ];

            $response = $this->getSoapClient()->GetPriceList($params);

            AltaID::truncate();
            Product::where('sku', 'LIKE', '%ALTA-%')->update(['show' => 0]);

            $items = $response->PriceList->items->item;

            if (!is_array($items)) {
                $items = [$items];
            }

            $altaIds = [];

            foreach ($items as $item) {
                $parsed   = $this->parseQtyText($item->qty_text ?? null);
                $quantity = $parsed['quantity'];
                $show     = $quantity > 2 ? 1 : 0;

                $altaIds[] = [
                    'product_id' => (string) $item->item,
                    'quantity'   => $quantity,
                ];

                Product::where('sku', 'ALTA-' . $item->item)->update([
                    'show'     => $show,
                    'quantity' => $quantity,
                    'in_stock' => $show,
                    'active'   => $show,
                ]);

                Log::info('ALTA-' . $item->item . ' Quantity: ' . $quantity . ' Show: ' . $show);
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
    // B2B Check — GET /alta/check?sku=ABC123
    // ============================================

    public function check(Request $request)
    {
        $sku = trim($request->get('sku', ''));

        if (empty($sku)) {
            return response()->json(['error' => 'sku პარამეტრი საჭიროა'], 400);
        }

        try {
            $params = [
                'user'     => 'UNIPRO_CHI',
                'password' => 'CHI1457160',
                'item'     => $sku,
            ];

            $response = $this->getSoapClient()->GetPriceList($params);

            $items = $response->PriceList->items->item ?? null;

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
            if (empty($qtyText)) {
                return ['has_stock' => false, 'quantity' => 0];
            }

            $qtyText = strtolower(trim($qtyText));

            if (strpos($qtyText, '>=') === 0) {
                $minQty = (int) str_replace('>=', '', $qtyText);
                return ['has_stock' => true, 'quantity' => max($minQty, 10)];
            }

            if (strpos($qtyText, '>') === 0) {
                $qty = (int) preg_replace('/[^0-9]/', '', $qtyText);
                return ['has_stock' => $qty > 0, 'quantity' => $qty];
            }

            if (is_numeric($qtyText)) {
                $qty = (int) $qtyText;
                return ['has_stock' => $qty > 0, 'quantity' => $qty];
            }

            if (preg_match('/out|not|unavailable|უ/i', $qtyText)) {
                return ['has_stock' => false, 'quantity' => 0];
            }

            if (preg_match('/in stock|available|ხელმ|აქვ/i', $qtyText)) {
                return ['has_stock' => true, 'quantity' => 5];
            }

            return ['has_stock' => true, 'quantity' => 5];

        } catch (Exception $e) {
            Log::warning("⚠️  qty_text პარსის შეცდომა: {$qtyText} - {$e->getMessage()}");
            return ['has_stock' => false, 'quantity' => 0];
        }
    }
}