<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;

class VerifyUserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $username = $request->input('username');
        $password = $request->input('password');

        if (!$username || !$password)
            return response()->json(['error' => 'Unauthorized access. Use credentials to login.'], 401);

        $user = User::query()->where(['name' => $username, 'password' => $password], '=', [$username, $password])->first();

        if (!$user)
            return response()->json(['error' => 'Invalid credentials.'], 401);

        $request->attributes->set('user', $user);

        return $next($request);
    }
}
