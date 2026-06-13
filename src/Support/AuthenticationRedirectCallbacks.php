<?php

declare(strict_types=1);

namespace Flatpack\Support;

use Flatpack\Http\FlatpackRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Wraps Laravel's guest / authenticated redirect callbacks so Flatpack URLs are handled
 * in the package without requiring changes in the host application's bootstrap.
 */
final class AuthenticationRedirectCallbacks
{
    public static function register(): void
    {
        self::wrapAuthenticationException();
        self::wrapRedirectIfAuthenticated();
    }

    private static function wrapAuthenticationException(): void
    {
        AuthenticationException::redirectUsing(function (Request $request): string {
            if (FlatpackRequest::matches($request)) {
                return route('flatpack.login');
            }

            if (Route::has('login')) {
                return route('login');
            }

            return '/';
        });
    }

    private static function wrapRedirectIfAuthenticated(): void
    {
        RedirectIfAuthenticated::redirectUsing(function (Request $request): ?string {
            if (FlatpackRequest::isFlatpackLoginRoute($request)) {
                return route('flatpack.dashboard');
            }

            return null;
        });
    }
}
