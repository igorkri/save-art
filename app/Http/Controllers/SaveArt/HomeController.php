<?php

namespace App\Http\Controllers\SaveArt;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {
        return view('saveart.home.index');
    }
}

