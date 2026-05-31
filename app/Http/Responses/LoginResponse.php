<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        $redirectTo = $user->isAdmin()
            ? route('admin.dashboard')
            : route('dashboard');

        return redirect()->intended($redirectTo);
    }
}
