<?php
namespace Pofol\Kernel;

use Pofol\Controller\Controller;
use Pofol\Injector\Injector;
use Pofol\Middleware\Middleware;
use Pofol\Request\Request;
use Pofol\Response\NoResponseException;
use Pofol\Response\Response;
use Pofol\Router\Route;
use Pofol\Router\Router;
use Pofol\Session\SessionService;

class Kernel
{
    protected $request;
    
    public function __construct()
    {
        $this->request = new Request;    
    }

    public function app()
    {
        ob_start();
        $this->bootSession();

        $route = $this->bootRouter();
        $response = $this->bootMiddleware();

        if (!$response instanceof Response) {

            throw new NoResponseException;

        } else if ($response->isRedirect()) {

            exit;

        }

        $action = $this->bootController($route);
        $response = $this->handleAction($route, $action);
        $response->send();

        ob_flush();
    }

    protected function bootSession()
    {
        $session = new SessionService();
        $session->boot();
    }

    protected function bootRouter()
    {
        $router = new Router($this->request);
        return $router->boot();
    }

    protected function bootMiddleware()
    {
        $middleware = new Middleware($this->request);
        return $middleware->boot();
    }

    protected function bootController(Route $route)
    {
        $controller = new Controller($route);
        return $controller->boot();
    }

    protected function handleAction(Route $route, $action)
    {
        if (is_array($action)) {

            $params = $route->getParams();
            $response = Injector::method($action[0], $action[1], ...$params);

        } else {

            $response = $action();

        }

        if ($response instanceof Response) {

            return $response;

        } elseif (is_string($response)) {

            echo $response;

            $response = new Response();
            $response->str();
            return $response;

        } else {

            throw new NoResponseException;

        }
    }
}
