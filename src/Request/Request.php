<?php
namespace Pofol\Request;

use Pofol\Support\Str;

class Request
{
    protected $url;
    protected $urlPattern;

    public function __construct()
    {
        $this->url = $_SERVER['REQUEST_URI'];
        $this->urlPattern = explode('/', $this->url());
    }

    public function query($key = null, $value = null)
    {
        if ($key !== null) {
            return $_GET[$key];
        }

        if (empty($_GET[$key])) {
            return $value;
        }

        return $_GET;
    }

    public function input($key = null, $value = null)
    {
        if ($key !== null) {
            return $_POST[$key];
        }

        if (empty($_POST[$key])) {
            return $value;
        }

        return $_POST;
    }

    public function method()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function url()
    {
        return Str::qualifyUrl($this->url);
    }

    public function fullUrl()
    {
        return $this->url;
    }

    public function urlPattern()
    {
        return $this->urlPattern;
    }
}
