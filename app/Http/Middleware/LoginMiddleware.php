<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;

class LoginMiddleware
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
            return response()->json(['status' => 'error', 'message' => 'Unauthorized access. Use credentials to login.'], 401);

        $username = strtolower($username);
        $user = User::query()->firstWhere('name', '=', $username);

        if (!$user || !password_verify($password, $user->password))
            return response()->json(['status' => 'error', 'message' => 'Invalid credentials.'], 401);

        $request->attributes->set('user', $user);

        return $next($request);
    }
}
