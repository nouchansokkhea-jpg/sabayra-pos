<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = array_keys(config('menuhub.locales', ['en' => 'English', 'km' => 'Khmer']));

        $locale = $request->get('lang')
            ?? session('locale')
            ?? $request->user()?->preferred_locale
            ?? config('menuhub.default_locale', 'en');

        if (in_array($locale, $supportedLocales, true)) {
            App::setLocale($locale);
            session(['locale' => $locale]);
        }

        return $next($request);
    }
}
