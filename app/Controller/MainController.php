<?php
namespace App\Controller;

use Pofol\Request\Request;

class MainController extends Controller
{
    public function index(Request $req)
    {
        return response()->view('child', [
            'a' => '변수 a값임.',
        ]);
    }
}
