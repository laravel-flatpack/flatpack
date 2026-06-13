<?php

declare(strict_types=1);

namespace Flatpack\Http\Middleware;

use Closure;
use Flatpack\Http\FlatpackRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Runs after the app's HandleInertiaRequests so the Flatpack Blade root (and built assets)
 * are used for all Flatpack URLs.
 */
final class SetFlatpackInertiaRootView
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (FlatpackRequest::matches($request)) {
            Inertia::setRootView('flatpack::app');
        }

        return $next($request);
    }
}
