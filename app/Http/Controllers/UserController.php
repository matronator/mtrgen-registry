<?php

namespace App\Http\Controllers;

use App\Models\User;
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

        if (User::query()->firstWhere('name', '=', $username))
            return response()->json(['error' => 'User with this username already exists.'], 400);

        // $user = $request->attributes->get('user');

        $user = User::query()->create([
            'name' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $user->name = $username;
        $user->password = password_hash($password, PASSWORD_DEFAULT);
        $user->save();

        return response()->json(['success' => 'User ' . $user->name . ' created.']);
    }

    public function login(Request $request)
    {

    }
}
