<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{

    public function show()
    {

        /** @var \App\Models\User|null $user */
        $user = auth()->guard()->user();

        return response()->json((new UserResource($user))->resolve());
    }

    public function update(Request $request)
    {

        /** @var \App\Models\User|null $user */
        $user = auth()->guard()->user();


        $validated = $request->validate([
            'name' => 'string|max:255',
            'password' => 'string|min:8|confirmed',
            'previous_password' => 'required_with:password|string',
        ]);

        if (isset($validated['password'])) {
            if (Hash::check($validated['previous_password'], $user->password)) {
                return response()->json(['message' => 'Previous password is incorrect'], 422);
            }
            $validated['password'] = Hash::make($validated['password']);
        }
        

        $user->update($validated);

        
        return response()->json([
            'message' => 'User updated',
            ...(new UserResource($user))->resolve()
        ]);
    }

    public function destroy()
    {

        /** @var \App\Models\User|null $user */
        $user = auth()->guard()->user();

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
