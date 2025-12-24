<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth('web')->check()) {
            return redirect()->route('login');
        }

        if (auth('web')->user()->role !== 'admin') {
            abort(403, 'Admins only.');
        }

        return $next($request);
    }
}
