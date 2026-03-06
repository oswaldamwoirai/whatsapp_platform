<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, \Closure $next)
    {
        return parent::handle($request, $next);
    }
}
