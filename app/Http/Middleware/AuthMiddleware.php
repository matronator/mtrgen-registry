<?php

namespace App\Http\Middleware;

use App\Models\AccessToken;
use App\Models\User;
use Closure;
use DateTime;

class AuthMiddleware
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
        $token = $request->input('token');

        if (!$username || !$token)
            return response()->json(['status' => 'error', 'message' => 'Unauthorized access. Please login.'], 401);
        
        $username = strtolower($username);
        $token = AccessToken::query()->firstWhere('token', '=', $token);

        if (!$token)
            return response()->json(['status' => 'error', 'message' => 'Token not found.'], 401);

        if (new DateTime($token->expires_at) <= new DateTime()) {
            $token->delete();
            return response()->json(['status' => 'error', 'message' => 'Access token expired, please login again.', 401]);
        }

        $user = User::query()->find($token->user_id);
        if (!$user)
            return response()->json(['status' => 'error', 'message' => 'User with this access token not found.'], 401);

        $request->attributes->set('user', $user);

        return $next($request);
    }
}
