<?php
namespace Pofol\Response;

use Pofol\View\View;

class Response
{
    protected $statusCode;
    protected $headers;
    protected $isRedirect = false;
    protected $view;

    public function __construct($statusCode = null, array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function statusCode($statusCode)
    {
        $this->statusCode = (int)$statusCode;

        return $this;
    }

    public function headers(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    public function send()
    {
        $this->setStatusCode();
        $this->setHeaders();

        if ($this->view !== null) {
            $this->view->view();
        }
    }

    public function redirect($location)
    {
        header("Location: $location");
        $this->isRedirect = true;
    }

    public function isRedirect()
    {
        return $this->isRedirect;
    }

    public function view($fileName, array $variables = [])
    {
        $this->view = View::get($fileName)->bind($variables);
        return $this;
    }

    protected function setStatusCode()
    {
        if ($this->statusCode !== null) {
            http_response_code($this->statusCode);
        }
    }

    protected function setHeaders()
    {
        foreach($this->headers as $header => $value) {
            $header = ucwords(strtolower($header), "-");
            header("{$header}: $value");
        }
    }
}
