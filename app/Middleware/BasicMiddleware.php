<?php
namespace App\Middleware;

use Closure;
use Pofol\Request\Request;

class BasicMiddleware extends Middleware
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
