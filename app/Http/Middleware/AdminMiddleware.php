<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // If not logged in -> redirect to login
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // If logged in but NOT admin -> 403 or redirect to home
        if (!auth()->user()->isAdmin()) {
            abort(403, 'You are not allowed to access this page.');
            // or: return redirect()->route('home');
        }

        return $next($request);
    }
}
