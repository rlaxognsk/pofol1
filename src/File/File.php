<?php
namespace Pofol\File;

use finfo;
use Pofol\Router\HttpNotFoundException;

class File
{
    protected $fileName;

    private function __construct($fileName)
    {
        $this->fileName = $fileName;
    }

    public static function get($fileName)
    {
        return new File($fileName);
    }

    public function file()
    {
        $path = __PF_ROOT__ . '\\storage\\' . $this->fileName;

        if (!file_exists($path)) {
            throw new HttpNotFoundException();
        }

        $fp = fopen($path, 'rb');

        $finfo = new finfo(FILEINFO_MIME);
        $type = $finfo->file($path);
        $mime = explode(';', $type);

        header('Content-Type: ' . $mime[0]);
        header('Content-Length: ' . filesize($path));

        fpassthru($fp);
    }
}
