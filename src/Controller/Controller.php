<?php
namespace Pofol\Controller;

use Pofol\Injector\Injector;
use Pofol\PofolService\PofolService;
use Pofol\Router\Route;
use Pofol\Router\InvalidRouteException;
use ReflectionMethod;

class Controller implements PofolService
{
    protected $route;

    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    public function boot()
    {
        return $this->parseAction();
    }

    protected function parseAction()
    {
        $action = $this->route->getAction();

        if (is_string($action)) {

            $parse = explode('@', $action);

            if (count($parse) !== 2) {

                throw new InvalidRouteException("Controller@method 형식이 아닙니다.");

            }

            $parsedAction[] = 'App\\Controller\\' . $parse[0];
            $parsedAction[] = $parse[1];

            return $parsedAction;

        } elseif(is_callable($action)) {

            return $action;

        } else {

            throw new InvalidRouteException("Controller@method 형식 혹은 Closure가 아닙니다.");

        }
    }
}
