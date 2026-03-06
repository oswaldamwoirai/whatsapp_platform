<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\ValidateSignature as Middleware;

class ValidateSignature extends Middleware
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, \Closure $next)
    {
        return parent::handle($request, $next);
    }
}
