<?php
namespace Pofol\Middleware;

use Closure;
use Pofol\Request\Request;

abstract class BasicMiddleware
{
    abstract public function handle(Request $request, Closure $next);
}
