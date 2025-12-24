<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsTeacher
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth('web')->check()) {
            return redirect()->route('login');
        }

        if (auth('web')->user()->role !== 'teacher') {
            abort(403, 'Teachers only.');
        }

        return $next($request);
    }
}
