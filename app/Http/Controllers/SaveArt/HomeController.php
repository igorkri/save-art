<?php

namespace App\Http\Controllers\SaveArt;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {
//        session()->flash('notification', [
//            'title' => 'Заголовок',
//            'message' => 'Текст уведомления',
//            'class' => 'green', // или любой другой класс
//        ]);

        return view('saveart.home.index');
    }
}

