<?php
namespace App\Controller;

use Pofol\Request\Request;

class MainController extends Controller
{
    public function index(Request $req)
    {
        return response()->view('test', ['hi' => 'Hello, World']);
    }
}
