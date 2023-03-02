<?php

namespace App\Http\Controllers;

use App\Helpers\BasicResponse;
use App\Models\AccessToken;
use App\Models\User;
use DateTime;
use Faker\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    const AVATAR_DIR = 'users/avatars/';

    public function findAll()
    {
        return response()->json(User::all());
    }

    public function findByName(string $username)
    {
        $username = strtolower($username);
        return response()->json(User::query()->firstWhere('username', '=', $username));
    }

    public function getLoggedUser(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
        ]);

        $user = $request->attributes->get('user');

        if (!$user)
            return BasicResponse::send('User not logged in.', 'error', '401');

        return response()->json(User::query()->find($user->id));
    }

    public function isUserLoggedIn(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
        ]);

        $user = $request->attributes->get('user', null);
        if ($user) {
            return response()->json(['status' => 'success', 'message' => 'User is logged in.', 'loggedIn' => true]);
        } else {
            return response()->json(['status' => 'success', 'message' => 'User is not logged in.', 'loggedIn' => false]);
        }
    }

    public function logout(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
        ]);

        $token = $request->header('Authorization');
        $token = str_replace('Bearer ', '', $token);

        $user = $request->attributes->get('user', null);

        if ($user) {
            $token = AccessToken::query()->firstWhere('token', '=', $token);
            $token->delete();
            return response()->json(['status' => 'success', 'message' => 'User logged out.']);
        } else {
            return response()->json(['status' => 'error', 'message' => 'User was not logged in.']);
        }
    }

    public function create(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
            'password' => 'required|string',
            'email' => 'sometimes|nullable|email',
        ]);

        $username = $request->input('username');
        $password = $request->input('password');
        $email = $request->input('email');

        if (!$username || !$password)
            return BasicResponse::send('Credentials missing.', BasicResponse::STATUS_ERROR, 400);

        $username = strtolower($username);
        
        if (User::query()->firstWhere('username', '=', $username))
            return BasicResponse::send('User with this username already exists.', BasicResponse::STATUS_ERROR, 400);
        
        if ($email && User::query()->firstWhere('email', '=', $email))
            return BasicResponse::send('This email is already in use.', BasicResponse::STATUS_ERROR, 400);

        // $user = $request->attributes->get('user');

        $user = User::query()->create([
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'email' => $email,
        ]);
        $user->save();

        return response()->json(['status' => 'success', 'message' => 'User ' . $user->username . ' created. You can now login.']);
    }

    public function setAvatar(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
            'avatar' => 'sometimes|nullable|image|mimes:png,jpg',
        ]);

        $avatar = $request->file('avatar');
        $user = $request->attributes->get('user', null);

        if (!$user) return BasicResponse::send('User not logged in.', 'error', 400);

        $path = self::AVATAR_DIR . $user->username;
        if (!$avatar) {
            Storage::deleteDirectory($path);
            $user->avatar = null;
            $user->save();

            return BasicResponse::send('Avatar deleted.');
        }
        
        Storage::deleteDirectory($path);
        $filename = $avatar->store($path);
        $user->avatar = $filename;
        $user->avatar_url = url('/api/users/' . $user->username . '/avatar');
        $user->save();

        return BasicResponse::send('Avatar saved.');
    }

    public function getAvatar(string $username)
    {
        $user = User::query()->firstWhere('username', '=', $username);
        if (!$user) return BasicResponse::send('User not found.', 'error', 404);

        if (!$user->avatar) return BasicResponse::send('User has no avatar set.', 'error', 400);

        return Storage::response($user->avatar);
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string|alpha_dash',
            'email' => 'sometimes|nullable|email',
            'fullname' => 'sometimes|nullable|string',
            'description' => 'sometimes|nullable|string',
            'website' => 'sometimes|nullable|url',
            'github' => 'sometimes|nullable|string',
        ]);

        $data = $request->except(['username']);
        $user = $request->attributes->get('user', null);

        if (!$user) return BasicResponse::send('User not logged in.', 'error', 400);

        User::query()->find($user->id)->update($data);

        return BasicResponse::send('User updated!');
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
            $expiresAt = new DateTime('2037-12-12');
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
            'user' => $user,
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
