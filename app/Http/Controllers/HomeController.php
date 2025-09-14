<?php

namespace App\Http\Controllers;

use App\Models\HomePage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        // Получаем текущую локаль (по умолчанию украинский)
        $locale = $request->get('lang', 'en');

        // Получаем активную страницу из админ-панели
        $homePage = HomePage::getActive();

        // Если нет активной страницы, создаем базовую с factory
        if (!$homePage) {
            $homePage = HomePage::factory()->create();
        }

//        phpinfo();
//        dd($homePage, $locale, $homePage->getTranslation('donates_subtitle', 'en'));

        return view('home', [
            'homePage' => $homePage,
            'locale' => $locale,
        ]);
    }
}

