<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PaycomMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('Authorization');

        if (
            !$header || !preg_match('/^\s*Basic\s+(\S+)\s*$/i', $header, $matches) ||
            base64_decode($matches[1]) != config('paycom.login') . ":" . config('paycom.key')
        ) {
            // Unauthorized response if token not there
            return response()->json([
                'id'    => $request->input('id'),
                'error' => [
                    'code'    => -32504,
                    'message' => "Insufficient privilege to perform this method."
                ]
            ], 200);
        }

        return $next($request);
    }
}
