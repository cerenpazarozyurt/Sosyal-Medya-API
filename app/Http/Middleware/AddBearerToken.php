<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddBearerToken
{
    /**
     * Handle an incoming request.
     * Eğer Authorization header'ında Bearer prefix'i yoksa otomatik ekler.
     * Böylece kullanıcı direkt token'ı kopyalayıp yapıştırabilir.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization');
        
        // Authorization header varsa ve Bearer prefix'i yoksa ekle
        if ($authHeader && !str_starts_with($authHeader, 'Bearer ')) {
            $request->headers->set('Authorization', 'Bearer ' . $authHeader);
        }
        
        return $next($request);
    }
}
