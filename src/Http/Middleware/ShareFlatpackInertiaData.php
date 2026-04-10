<?php

declare(strict_types=1);

namespace Flatpack\Http\Middleware;

use Closure;
use Flatpack\Flatpack;
use Flatpack\Http\FlatpackRequest;
use Flatpack\Http\Resources\FlatpackUser;
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
                'quickAction' => config('flatpack.quick_action'),
                'menu' => array_map(
                    static fn ($item): array => $item->toArray(),
                    $this->flatpack->menu(),
                ),
                'secondaryMenu' => config('flatpack.secondary_menu'),
                'bottomMenu' => config('flatpack.bottom_menu'),
                'pages' => [
                    'dashboard' => route('flatpack.dashboard'),
                    'login' => route('flatpack.login'),
                    'logout' => route('flatpack.logout'),
                ],
                'user' => $request->user() ? FlatpackUser::make($request->user()) : null,
            ]);
        }

        return $next($request);
    }
}
