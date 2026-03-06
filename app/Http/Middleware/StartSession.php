<?php

namespace App\Http\Middleware;

use Illuminate\Session\Middleware\StartSession as Middleware;

class StartSession extends Middleware
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, \Closure $next)
    {
        return parent::handle($request, $next);
    }
}
