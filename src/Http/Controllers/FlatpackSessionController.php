<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class FlatpackSessionController
{
    /**
     * Show the Flatpack login page (no register / forgot password links from the package).
     */
    public function create(Request $request): Response
    {
        $this->stashFlatpackIntendedUrl($request);

        return Inertia::render('login', [
            'canResetPassword' => false,
            'canRegister' => false,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Attempt session authentication for Flatpack (Laravel auth; no Fortify required).
     *
     * To use Fortify or another package, set config `flatpack.login.store` to your controller action.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->restoreFlatpackIntendedUrl($request);

        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $guard = Auth::guard((string) config('flatpack.guard', 'web'));

        if (! $guard instanceof StatefulGuard) {
            abort(500, 'Flatpack login requires a stateful guard (e.g. web).');
        }

        if (! $guard->attempt(
            $request->only('email', 'password'),
            $request->boolean('remember'),
        )) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('flatpack.dashboard'));
    }

    /**
     * Destroy the Flatpack session using the configured guard.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $guard = Auth::guard((string) config('flatpack.guard', 'web'));

        if (! $guard instanceof StatefulGuard) {
            abort(500, 'Flatpack logout requires a stateful guard (e.g. web).');
        }

        $guard->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('flatpack.login');
    }

    private function stashFlatpackIntendedUrl(Request $request): void
    {
        $intended = $request->session()->get('url.intended');

        if (! is_string($intended) || ! $this->isFlatpackUrl($intended)) {
            return;
        }

        $request->session()->put('flatpack.url.intended', $intended);
        $request->session()->forget('url.intended');
    }

    private function restoreFlatpackIntendedUrl(Request $request): void
    {
        $intended = $request->session()->pull('flatpack.url.intended');

        if (! is_string($intended) || ! $this->isFlatpackUrl($intended)) {
            return;
        }

        $request->session()->put('url.intended', $intended);
    }

    private function isFlatpackUrl(string $url): bool
    {
        $prefix = trim((string) config('flatpack.prefix', 'flatpack'), '/');
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        if ($path === '') {
            return false;
        }

        if ($path === $prefix || Str::startsWith($path, $prefix . '/')) {
            return true;
        }

        // If the app runs under a subdirectory, treat `/subdir/flatpack/...` as Flatpack too.
        return Str::contains($path, '/' . $prefix . '/') || Str::endsWith($path, '/' . $prefix);
    }
}
