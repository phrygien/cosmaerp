<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('X-Webhook-Secret');

        if (!$signature || !hash_equals(env('WEBHOOK_SECRET'), $signature)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}