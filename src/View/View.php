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
                // child 컨텍스트의 $file변수가 parent 컨텍스트의 $file변수로 덮어 씌어진다.
                // 따라서 현재 컨텍스트에 존재하는 변수는 그냥 pass시킨다.
                if (isset($$variable)) {
                    continue;
                }

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
    }

    public function bind(array $variables)
    {
        $this->variables = $variables;

        return $this;
    }
}
