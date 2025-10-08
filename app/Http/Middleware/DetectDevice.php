<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Detection\MobileDetect;

class DetectDevice
{
    public function handle($request, Closure $next)
    {
        $detect = new MobileDetect();
        $device = 'desktop';
        if ($detect->isTablet()) {
            $device = 'tablet';
        } elseif ($detect->isMobile()) {
            $device = 'mobile';
        }
        // Можно сохранить в сессию или share для view
        session(['device_type' => $device]);
        view()->share('device_type', $device);

        return $next($request);
    }
}
