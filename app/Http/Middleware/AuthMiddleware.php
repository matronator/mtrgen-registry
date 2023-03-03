<?php

namespace App\Http\Middleware;

use App\Helpers\BasicResponse;
use App\Models\AccessToken;
use App\Models\User;
use Closure;
use DateTime;
use Illuminate\Support\Facades\Log;

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
        $token = $request->header('Authorization');
        Log::debug($token);
        
        if (!$username || !$token)
            return response()->json(['status' => 'error', 'message' => 'Unauthorized access. Please login.'], 401)->header('WWW-Authenticate', 'Bearer');
        
        $token = str_replace('Bearer ', '', $token);
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

        if (strtolower($user->username) !== $username)
            return BasicResponse::send('Not authorized!', 'error', 401);
        
        $request->attributes->set('user', $user);

        return $next($request);
    }
}
