<?php
namespace Pofol\Router;

use Pofol\Request\Request;

class RouteCollection
{
    protected $routes = [];
    protected $request;

    public function __construct(Request $req)
    {
        $this->request = $req;
    }

    public function add(Route $route)
    {
        $this->routes[] = $route;
    }

    public function findMatch()
    {
        for ($i = 0, $len = count($this->routes); $i < $len; $i++) {
            $route = $this->routes[$i];

            if (!$this->checkMethod($route)) {
                continue;
            }

            if ($params = $this->checkURL($route)) {
                if (is_array($params)) {
                    $route->setParams($params);
                }
                return $route;
            }
        }

        throw new HttpNotFoundException;
    }

    protected function checkMethod(Route $route)
    {
        if ($route->getMethod() === $this->request->method()) {
            return true;
        }
        return false;
    }

    protected function checkURL(Route $route)
    {
        if ($route->getUrl() === $this->request->url()) {
            return true;
        }

        $patterns = explode('/', $route->getUrl());
        $currentURLPatterns = $this->request->urlPattern();

        $params = [];

        if (count($patterns) !== count($currentURLPatterns)) {
            return false;
        }

        $endPoint = count($patterns) - 1;

        for ($i = 1; $i < $endPoint; $i++) {
            if ($currentURLPatterns[$i] === $patterns[$i]) {
                continue;
            } elseif (strpos($patterns[$i], ':') === 0) {
                $params[] = $currentURLPatterns[$i];
            } else {
                return false;
            }
        }

        return $params;
    }
}
