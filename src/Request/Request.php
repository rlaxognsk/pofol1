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

    public function query($key, $value = null)
    {
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }

        return $value;
    }

    public function input($key = null, $value = null)
    {
        if (isset($_POST[$key])) {
            return $_POST[$key];
        }

        return $value;
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

    public function token()
    {
        if (isset($_POST['_token'])) {
            return $_POST['_token'];
        }

        return null;
    }
}
