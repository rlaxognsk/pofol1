<?php
namespace Pofol\Middleware;

use Closure;
use Pofol\Request\Request;

class CSRFTokenHandleMiddleware extends BasicMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->checkCSRFToken($request)) {
            $this->setCSRFToken();
            return $next($request);
        }

        return redirect('/error');
    }

    protected function checkCSRFToken(Request $req)
    {
        if ($req->method() !== 'POST') {
            return true;
        }

        if ($req->token() === $_SESSION['_token']) {
            return true;
        } else {
            return false;
        }
    }

    protected function setCSRFToken()
    {
        $_SESSION['_token'] = randString();
    }
}
