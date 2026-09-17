<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;
use App\Http\Resources\UserResource;
use App\Jobs\ProcessEmail;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role') && $request->role !== '') {
            $query->where('role', $request->role);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($request->per_page ?? 50);

        return UserResource::collection($users);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role' => ['required', new Enum(UserRole::class)]
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role']
        ]);

        if (! $user->hasVerifiedEmail()) {
            $emailData = ['user' => $user];
            $welcomeEmail = new WelcomeEmail($emailData);

            ProcessEmail::dispatch($user, $welcomeEmail);
        }

        return response()->json([
            'message' => 'User created successfully',
            ...(new UserResource($user))->resolve()
        ], 201);
    }

    public function show(string $id)
    {
        $user = User::findOrFail($id);

        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth()->guard()->user();

        if (!$currentUser->isAdmin() && $currentUser->id != $id) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        return new UserResource($user);
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth()->guard()->user();

        if (!$currentUser->isAdmin() && $currentUser->id != $id) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        $rules = [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id)
            ],
            'role' => ['required', new Enum(UserRole::class)]
        ];

        if ($currentUser->isAdmin()) {
            $validated = $request->validate($rules);
        } else {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($user->id)
                ]
            ]);
            $validated['role'] = UserRole::USER->value;
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated',
            ...(new UserResource($user))->resolve()
        ]);
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        /** @var \App\Models\User|null $currentUser */
        $currentUser = auth()->guard()->user();

        if (!$currentUser->isAdmin()) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

        if ($currentUser->id == $id) {
            return response()->json(['message' => 'Cannot delete your own account'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
