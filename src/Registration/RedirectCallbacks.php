<?php

declare(strict_types=1);

namespace Flatpack\Registration;

use Flatpack\Http\FlatpackRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use ReflectionProperty;

/**
 * Wraps Laravel's guest / authenticated redirect callbacks so Flatpack URLs are handled
 * in the package without requiring changes in the host application's bootstrap.
 */
final class RedirectCallbacks
{
    public static function register(): void
    {
        self::wrapAuthenticationException();
        self::wrapRedirectIfAuthenticated();
    }

    private static function wrapAuthenticationException(): void
    {
        $reflection = new ReflectionProperty(AuthenticationException::class, 'redirectToCallback');
        $previous = $reflection->getValue();

        AuthenticationException::redirectUsing(function (Request $request) use ($previous) {
            if (FlatpackRequest::matches($request)) {
                return route('flatpack.login');
            }

            if (is_callable($previous)) {
                return $previous($request);
            }

            if (Route::has('login')) {
                return route('login');
            }

            return '/';
        });
    }

    private static function wrapRedirectIfAuthenticated(): void
    {
        $reflection = new ReflectionProperty(RedirectIfAuthenticated::class, 'redirectToCallback');
        $previous = $reflection->getValue();

        RedirectIfAuthenticated::redirectUsing(function (Request $request) use ($previous) {
            if (FlatpackRequest::isFlatpackLoginRoute($request)) {
                return route('flatpack.dashboard');
            }

            if (is_callable($previous)) {
                return $previous($request);
            }

            return null;
        });
    }
}
