<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorLoginResponse implements TwoFactorLoginResponseContract
{
    public function toResponse($request): Response
    {
        $home = $request->user()?->is_admin ? '/admin' : '/dashboard';

        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->intended($home);
    }
}
