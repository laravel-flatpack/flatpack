<?php

declare(strict_types=1);

namespace Flatpack\Http\Middleware;

use Closure;
use Flatpack\Flatpack;
use Flatpack\Http\FlatpackRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final readonly class ShareFlatpackInertiaData
{
    public function __construct(
        private Flatpack $flatpack,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (FlatpackRequest::matches($request)) {
            Inertia::share('flatpack', [
                'menu' => array_map(
                    static fn ($item): array => $item->toArray(),
                    $this->flatpack->menu(),
                ),
            ]);
        }

        return $next($request);
    }
}
