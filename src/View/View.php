<?php
namespace Pofol\View;

use Exception;
use Pofol\View\PFTEngine\PFTEngine;

class View
{
    protected $fileName;
    protected $variables = [];

    public function __construct($fileName)
    {
        $this->fileName = $fileName;
    }

    public static function get($fileName)
    {
        return new View($fileName);
    }

    public function view()
    {
        $path = str_replace('.', '\\', $this->fileName);
        $file = __PF_ROOT__ . '\\view\\' . $path;

        if (!empty($this->variables)) {
            foreach ($this->variables as $variable => $value) {
                $$variable = $value;
            }
        }

        if (file_exists($file . '.pft.php')) {

            $engine = injector(PFTEngine::class);
            require $engine->get($file . '.pft.php');

        } elseif (file_exists($file . '.php')) {

            require $file . '.php';

        } else {

            throw new Exception('view 파일이 존재하지 않습니다.');

        }

        return $this;
    }

    public function bind(array $variables)
    {
        $this->variables = $variables;

        return $this;
    }

    // TODO: 부모-자식뷰 꼭 구현하기.
}
