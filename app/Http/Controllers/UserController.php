<?php

namespace App\Http\Controllers;

use App\Models\AccessToken;
use App\Models\User;
use DateInterval;
use DateTime;
use Faker\Factory;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function findAll()
    {
        return response()->json(User::all());
    }

    public function create(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
            'password' => 'required|string',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');

        if (!$username || !$password)
            return response()->json(['error' => 'Credentials missing.'], 400);

        $username = strtolower($username);
        
        if (User::query()->firstWhere('name', '=', $username))
            return response()->json(['error' => 'User with this username already exists.'], 400);

        // $user = $request->attributes->get('user');

        $user = User::query()->create([
            'name' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        $user->save();

        return response()->json(['success' => 'User ' . $user->name . ' created. You can now login.']);
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
            'password' => 'required|string',
            'duration' => 'required|numeric|integer'
        ]);

        $user = $request->attributes->get('user');

        $duration = (int) $request->input('duration');
        if ($duration !== 0) {
            // $expiresAt = (new DateTime())->add(DateInterval::createFromDateString("$duration hours"));
            $expiresAt = new DateTime("now + $duration hours");
        } else {
            $expiresAt = new DateTime('2030-12-12');
        }

        AccessToken::query()->where('user_id', '=', $user->id)->delete();

        $accessToken = AccessToken::query()->create([
            'user_id' => $user->id,
            'token' => $this->generateUniqueToken(),
            'expires_at' => $expiresAt,
        ]);
        if (!$accessToken->save())
            return response()->json(['status' => 'error', 'message' => 'Something went wrong.'], 400);

        return response()->json([
            'status' => 'success',
            'message' => 'Access token created.',
            'token' => $accessToken->token,
        ], 200);
    }

    private function generateUniqueToken(): string
    {
        $faker = Factory::create();
        $token = $faker->sha256();

        if (AccessToken::query()->firstWhere('token', '=', $token)) {
            return $this->generateUniqueToken();
        }

        return $token;
    }
}
