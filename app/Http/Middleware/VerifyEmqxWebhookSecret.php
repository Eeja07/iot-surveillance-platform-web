<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyEmqxWebhookSecret
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = env('EMQX_WEBHOOK_SECRET', 'default-super-secret-token');
        if ($request->header('x-webhook-secret') !== $secret) {
            return response()->json(['message' => 'Unauthorized webhook secret'], 401);
        }
        return $next($request);
    }
}
