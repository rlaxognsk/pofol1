<?php
namespace Pofol\View\PFTEngine;

class PFTEngine
{
    protected $compiler;

    public function __construct(Compiler $compiler)
    {
        $this->compiler = $compiler;
    }

    public function get($file)
    {
        $hash = sha1_file($file);
        $compiledFile = __DIR__ . '\\..\\Compiled\\' . $hash . '.php';

        if (!file_exists($compiledFile)) {
            file_put_contents(
                $compiledFile, $this->compiler->compile($file)
            );
        }

        return realpath($compiledFile);
    }
}
