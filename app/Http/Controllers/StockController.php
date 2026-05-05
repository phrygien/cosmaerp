<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\KafkaProducerService;

class StockController extends Controller
{
    public function webhook(Request $request, KafkaProducerService $kafka)
    {
        $data = $request->validate([
            'sku'   => 'required|string',
            'delta' => 'required|integer',
            'source' => 'required|integer', // exemple 'prestashop', 'magento', 'erp'
        ]);

        $kafka->publish($data['sku'], $data['delta'], $data['source']);

        return response()->json(['ok' => true]);
    }
}
