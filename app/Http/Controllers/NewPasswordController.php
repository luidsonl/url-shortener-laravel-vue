<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Str;
use App\Mail\ResetPasswordLink;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NewPasswordController extends Controller
{
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'If this email exists, a reset link has been sent.'], 200);
        }
        $token = Str::random(60);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => Hash::make($token), 'created_at' => Carbon::now()]
        );

        $frontendUrl = route('password.reset', ['token' => $token, 'email' => $request->email]);

        $resetPasswordLink = new ResetPasswordLink($frontendUrl);

        ProcessEmail::dispatch($user, $resetPasswordLink);

        return response()->json(['message' => 'If this email exists, a reset link has been sent.'], 200);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Invalid token'], 400);
            }
            return back()->withInput()->withErrors(['token' => 'Invalid or expired token.']);
        }

        if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Token expired'], 400);
            }
            return back()->withInput()->withErrors(['token' => 'Token expired.']);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'User not found'], 404);
            }
            return back()->withInput()->withErrors(['email' => 'User not found.']);
        }

        $user->forceFill([
            'password' => Hash::make($request->password)
        ])->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Password reset successfully'], 200);
        }

        return view('reset-password-success');
    }

    public function resetPasswordForm(Request $request)
    {
        $token = $request->query('token');
        $email = $request->query('email');

        return view('reset-password-form', ['token' => $token, 'email' => $email]);
    }
}
 