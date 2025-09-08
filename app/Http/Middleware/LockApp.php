<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LockApp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (config('app.locked') && ! $request->is('login') && ! $request->is('logout')) {
            if (auth()->check()) {
                return response()->view('errors::403', ['message' => 'This page has been locked, please contact administrator for further information'], 403);
            }
        }

        return $next($request);
    }
}
