<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocaleFromUrl
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($request->segment(1) === 'ua') {
            app()->setLocale('uk');
        } else {
            app()->setLocale('en');
        }
        return $next($request);
    }
}

