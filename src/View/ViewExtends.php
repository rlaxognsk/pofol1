<?php
namespace Pofol\View;

use Exception;

class ViewExtends
{
    protected static $singleton = null;
    protected $stack = [];
    protected $contents = [];

    private function __construct()
    {
        //
    }

    public static function instance()
    {
        if (self::$singleton === null) {
            self::$singleton = new ViewExtends;
            return self::$singleton;
        } else {
            return self::$singleton;
        }
    }

    public function addSection($sectionName)
    {
        ob_start();
        $this->stack[] = $sectionName;
    }

    public function endSection()
    {
        $contents = ob_get_clean();
        $recentSection = array_pop($this->stack);

        $this->contents[$recentSection] = $contents;
    }

    public function yieldSection($sectionName)
    {
        if (array_key_exists($sectionName, $this->contents)) {
            echo $this->contents[$sectionName];
        } else {
            throw new Exception("{$sectionName} section이 존재하지 않습니다.");
        }
    }

    public function extendParent($fileName, array $variables = [])
    {
        View::get($fileName)->bind($variables)->view();
    }
}
