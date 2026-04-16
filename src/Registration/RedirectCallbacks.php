<?php

declare(strict_types=1);

namespace Flatpack\Registration;

/** @deprecated Use Flatpack\Registration\AuthenticationRedirectCallbacks instead. */
final class RedirectCallbacks
{
    public static function register(): void
    {
        AuthenticationRedirectCallbacks::register();
    }
}
