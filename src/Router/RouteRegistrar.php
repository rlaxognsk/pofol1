<?php
namespace Pofol\Router;

use Closure;

class RouteRegistrar
{
    protected $collection;
    protected $routeGroupAttribute = [];

    public function __construct(RouteCollection $collection)
    {
        $this->collection = $collection;
    }

    public function get($url, $action)
    {
        return $this->addRouteToCollection('GET', $url, $action);
    }

    public function post($url, $action)
    {
        return $this->addRouteToCollection('POST', $url, $action);
    }

    public function update($url, $action)
    {
        return $this->addRouteToCollection('UPDATE', $url, $action);
    }

    public function delete($url, $action)
    {
        return $this->addRouteToCollection('DELETE', $url, $action);
    }

    public function put($url, $action)
    {
        return $this->addRouteToCollection('PUT', $url, $action);
    }

    public function options($url, $action)
    {
        return $this->addRouteToCollection('OPTIONS', $url, $action);
    }

    public function group(array $attr, Closure $routeList)
    {
        $this->routeGroupAttribute = $attr;
        $routeList();
        $this->routeGroupAttribute = [];
    }

    protected function addRouteToCollection($method, $url, $action)
    {
        if (empty($this->routeGroupAttribute)) {

            $route = $this->createRoute($method, $url, $action);
            $this->collection->add($route);

        } else {

            $prefix = isset($this->routeGroupAttribute['prefix']) ? $this->routeGroupAttribute['prefix'] : null;
            $route = $this->createRoute($method, $prefix . $url, $action);
            $this->collection->add($route);

        }

        return $route;
    }

    protected function createRoute($method, $url, $action)
    {
        return new Route($method, $this->prettyUrl($url), $action);
    }

    /**
     * url 형식을 /some/thing/ 으로 정규화한다.
     * @param $url string
     * @return string
     */
    protected function prettyUrl($url)
    {
        if ($url === '/') {

            return $url;

        }

        if ($url[0] !== '/') {

            $url = '/' . $url;

        }

        if ($url[strlen($url) - 1] !== '/') {

            $url .= '/';

        }

        $url = preg_replace('/\/{2,}/', '/', $url);

        return $url;
    }
}
