<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StockController;

Route::post('/stock/webhook', [StockController::class, 'webhook'])
    ->middleware('webhook.verify');
