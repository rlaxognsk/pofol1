<?php
namespace Pofol\Middleware;

use Pofol\PofolService\PofolService;
use Pofol\Request\Request;
use Pofol\Response\Response;
use ReflectionMethod;

class Middleware implements PofolService
{
    protected $request;
    protected $next;
    protected $queue = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->queue = array_reverse(config('middleware'));

        $reflection = new ReflectionMethod($this, "middlewareChain");
        $this->next = $reflection->getClosure($this);
    }

    public function boot()
    {
        return $this->middlewareChain();
    }

    protected function middlewareChain()
    {
        $middleware = array_pop($this->queue);

        if ($middleware === null) {
            return new Response;
        }

        $middleware = new $middleware;

        return $middleware->handle($this->request, $this->next);
    }
}
