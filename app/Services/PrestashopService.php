<?php 

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PrestashopService
{

    public function searchCombination(string $url_presta, string $sku){
        $response = Http::withBasicAuth(env('PRESTASHOP_API_KEY'), '')
            ->get($url_presta . "/api/combinations", [
                'filter[ean13]' => $sku,
                'display' => '[id,id_product,ean13]'
            ]);

        if (!$response->successful()) {
            return null;
        }

        $xml = simplexml_load_string($response->body());

        if (!isset($xml->combinations->combination)) {
            return null;
        }

        $combination = $xml->combinations->combination;

        return [
            'id' => (int) $combination->id,
            'id_product' => (int) $combination->id_product,
            'ean13' => (string) $combination->ean13,
        ];
    }

    public function searchProduct(string $url_presta, string $sku){
        $response = Http::withBasicAuth(env('PRESTASHOP_API_KEY'), '')
            ->get($url_presta . "/api/products", [
                'filter[ean13]' => $sku,
                'display' => '[id,ean13]'
            ]);
            
        if (!$response->successful()) {
            return null;
        }

        $xml = simplexml_load_string($response->body());

        if (!isset($xml->products->product)) {
            return null;
        }

        $product = $xml->products->product;

        return [
            'id' => (int) $product->id,
            'ean13' => (string) $product->ean13,
        ];
    }

    public function getProductbyReference(string $url_presta, string $sku){
        // 1. chercher dans combinations
        $combination = $this->searchCombination($url_presta, $sku);

        if ($combination !== null) {
            return [
                'type' => 'combination',
                'product_id' => $combination['id_product'],
                'combination_id' => $combination['id'],
                'ean13' => $combination['ean13']
            ];
        }

        // 2. fallback produit simple
        $product = $this->searchProduct($url_presta, $sku);

        if ($product !== null) {
            return [
                'type' => 'product',
                'product_id' => $product['id'],
                'ean13' => $product['ean13']
            ];
        }

        return null;
    }

    private function getStockAvailableId(string $url_presta, $productId, $combinationId = 0){
        $response = Http::withBasicAuth(env('PRESTASHOP_API_KEY'), '')
            ->get($url_presta . "/api/stock_availables", [
                'filter[id_product]' => $productId,
                'filter[id_product_attribute]' => $combinationId,
                'display' => '[id,quantity]'
            ]);

        if (!$response->successful()) {
            return null;
        }

        $xml = simplexml_load_string($response->body());

        if (!isset($xml->stock_availables->stock_available)) {
            return null;
        }

        return (int) $xml->stock_availables->stock_available->id;
    }

    public function updateStock(string $sku, int $quantity): void{
        $data = $this->getProductbyReference(env('PRESTASHOP_URL'), $sku);

        if (!$data) {
            throw new \Exception("Produit non trouvé");
        }

        $productId = $data['product_id'];
        $combinationId = $data['type'] === 'combination'
            ? $data['combination_id']
            : 0;

        // 🔥 récupérer stock_available_id
        $stockAvailableId = $this->getStockAvailableId(env('PRESTASHOP_URL'), $productId, $combinationId);

        if (!$stockAvailableId) {
            throw new \Exception("StockAvailable introuvable");
        }

        // ✅ envoyer XML (pas JSON !)
        $xml = "
        <prestashop>
            <stock_available>
                <id>{$stockAvailableId}</id>
                <quantity>{$quantity}</quantity>
            </stock_available>
        </prestashop>";

        Http::withBasicAuth(env('PRESTASHOP_API_KEY'), '')
            ->withHeaders(['Content-Type' => 'application/xml'])
            ->put(env('PRESTASHOP_URL') . "/api/stock_availables/{$stockAvailableId}", $xml);
    }
}