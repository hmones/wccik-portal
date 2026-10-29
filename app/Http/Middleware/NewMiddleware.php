<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NewMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($locale, ['en', 'ur'])) {
            $locale = config('app.locale');
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
