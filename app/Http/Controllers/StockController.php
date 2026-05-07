<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\KafkaProducerService;

use App\Models\Product;
use App\Models\StockMagasin;

class StockController extends Controller
{
    public function webhook(Request $request, KafkaProducerService $kafka)
    {
        $data = $request->validate([
            'sku'   => 'required|string',
            'delta' => 'required|integer',
            'source' => 'required|string', // exemple 'prestashop', 'magento', 'erp'
        ]);

        $product = Product::where('EAN', $data['sku'])->first();
        $magasinId = (int) env('STOCK_MAGASIN_ID');

        $nombre_stock = StockMagasin::where('product_id', $product->id)
            ->where('magasin_id', $magasinId)
            ->sum('nb_item');

        if($nombre_stock >= $data['delta']){
            $kafka->publish($data['sku'], $data['delta'], $data['source']);
            return response()->json(['ok' => true, 'stock_actuel' => $nombre_stock]);
        }else{
            return response()->json(['ok' => false, 'stock_actuel' => $nombre_stock]);
        }
    }
}
