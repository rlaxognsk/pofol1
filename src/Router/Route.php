<?php
namespace Pofol\Router;

class Route
{
    protected $url;
    protected $method;
    protected $action;
    protected $params = [];

    protected static $validMethod = [
        'GET', 'POST', 'UPDATE', 'DELETE', 'PUT', 'OPTIONS'
    ];

    public function __construct($method, $url, $action)
    {
        if (!in_array($method, self::$validMethod)) {

            throw new InvalidRouteException("{$method}는 지원하지 않는 메소드입니다.");

        } elseif (!is_string($action) && !is_callable($action)) {

            throw new InvalidRouteException("Controller@method 혹은 클로저를 라우트에 등록해야 합니다.");

        }

        $this->method = $method;
        $this->url = $url;
        $this->action = $action;
    }

    public function setParams(array $params)
    {
        $this->params = $params;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getAction()
    {
        return $this->action;
    }
}
