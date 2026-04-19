<?php

declare(strict_types=1);

namespace Flatpack\Http\Middleware;

use Closure;
use Flatpack\Facades\Flatpack;
use Flatpack\Http\FlatpackRequest;
use Flatpack\Http\Resources\FlatpackUser;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

final readonly class ShareFlatpackInertiaData
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (FlatpackRequest::matches($request)) {
            Inertia::share('flatpack', [
                'quickAction' => Flatpack::quickAction(),
                'menu' => array_map(
                    static fn ($item): array => $item->toArray(),
                    Flatpack::menu(),
                ),
                'secondaryMenu' => Flatpack::secondaryMenu(),
                'bottomMenu' => Flatpack::bottomMenu(),
                'showActionShortcutHints' => Flatpack::showActionShortcutHints(),
                'user' => $request->user() ? FlatpackUser::make($request->user()) : null,
            ]);
        }

        return $next($request);
    }
}
