<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Support\AuthenticatedUserRedirect;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class FortifyLoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = $request->user();

        return redirect()->to(AuthenticatedUserRedirect::for($user instanceof User ? $user : null));
    }
}
