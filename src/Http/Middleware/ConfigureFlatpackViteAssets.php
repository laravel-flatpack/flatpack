<?php

declare(strict_types=1);

namespace Flatpack\Http\Middleware;

use Closure;
use Flatpack\Http\FlatpackRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Point Laravel's Vite helper at the Flatpack build directory and hot file so @vite in
 * flatpack::app resolves assets from the package public tree (default: public/build; dev hot file public/hot).
 */
final class ConfigureFlatpackViteAssets
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (FlatpackRequest::matches($request)) {
            Vite::useHotFile(public_path('vendor/flatpack/hot'))
                ->useBuildDirectory('vendor/flatpack/build');
        }

        return $next($request);
    }
}
