<?php
namespace Pofol\Request;

class Request
{
    protected $url;
    protected $urlPattern;

    public function __construct()
    {
        $this->url = $_SERVER['REQUEST_URI'];
        $this->urlPattern = explode('/', $this->url());
    }

    protected function qualifyUrl($url = null)
    {
        if ($url === null) {
            $url = $this->url;
        }

        $url = preg_replace('/\?.*/', '', $url);

        if ($url[strlen($url) - 1] !== '/') {
            $url .= '/';
        }
        return $url;
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
        return $this->qualifyUrl();
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
