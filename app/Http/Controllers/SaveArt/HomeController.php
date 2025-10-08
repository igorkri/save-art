<?php

namespace App\Http\Controllers\SaveArt;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {


        $homePage = \App\Models\HomePage::getActive();
        // записываем язык на ua
        // app()->setLocale('ua');


        //        session()->flash('notification', [
        //            'title' => 'Заголовок',
        //            'message' => 'Текст уведомления',
        //            'class' => 'green', // или любой другой класс
        //        ]);

        return view('saveart.home.index', compact('homePage'));
    }
}
