<?php
namespace Pofol\Response;

use Exception;
use Pofol\DB\Model;
use Pofol\File\File;
use Pofol\View\View;
use stdClass;

class Response
{
    protected $statusCode;
    protected $headers = [];
    protected $isRedirect = false;
    protected $isReady = false;
    protected $view;
    protected $file;
    protected $json;

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
        foreach ($headers as $key => $value) {
            $this->headers[$key] = $value;
        }

        return $this;
    }

    public function send()
    {
        if (!$this->isReady) {
            throw new NoResponseException("응답 요소가 존재하지 않습니다.");
        }

        $this->setStatusCode();
        $this->setHeaders();

        if (isset($this->view)) {
            $this->view->view();
        } elseif (isset($this->file)) {
            $this->file->file();
        } elseif (isset($this->json)) {
            echo $this->json;
        }

        return;
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
        if ($this->isReady) {
            throw new Exception("이미 응답요소가 준비되어 있습니다.");
        }

        $this->view = View::get($fileName)->bind($variables);
        $this->isReady = true;
        return $this;
    }

    public function file($fileName)
    {
        if ($this->isReady) {
            throw new Exception("이미 응답요소가 준비되어 있습니다.");
        }

        $this->file = File::get($fileName);
        $this->isReady = true;
        return $this;
    }

    public function json($json)
    {
        if ($this->isReady) {
            throw new Exception("이미 응답요소가 준비되어 있습니다.");
        }

        if (is_array($json) || $json instanceof stdClass) {
            $this->json = json_encode($json);
        } elseif ($json instanceof Model) {
            $this->json = $json->toJson();
        } else {
            throw new Exception("배열 혹은 stdClass, 모델 객체를 넘겨야 합니다.");
        }

        header('Content-Type: application/json');

        $this->isReady = true;
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
