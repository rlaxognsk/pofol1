<?php
namespace Pofol\Router;

use Exception;
use Pofol\PofolService\PofolService;
use Pofol\Request\Request;

class Router implements PofolService
{
    protected $request;
    protected $collection;
    protected $registrar;

    public function __construct(Request $req)
    {
        $this->collection = new RouteCollection($req);
        $this->registrar = new RouteRegistrar($this->collection);
        $this->request = $req;
    }

    public function boot()
    {
        try {

            $this->addRoutes();
            return $this->findMatch();

        } catch (HttpNotFoundException $e) {

            http_response_code(404);
            echo '404 NOT FOUND';
            exit;

        } catch (Exception $e) {

            http_response_code(500);
            echo $e->getMessage();
            exit;

        }
    }

    protected function addRoutes()
    {
        // require로 가져온 Route List파일에서 변수로 사용될 것.
        $Route = $this->registrar;

        $namespace = __DIR__ . '/../../route';
        $routeList = config('route');

        for ($i = 0, $len = count($routeList); $i < $len; $i++) {

            require $namespace . '/' . $routeList[$i] . '.php';

        }
    }

    protected function findMatch()
    {
        $route = $this->collection->findMatch();

        return $route;
    }
}
