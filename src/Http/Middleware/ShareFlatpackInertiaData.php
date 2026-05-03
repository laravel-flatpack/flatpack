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
                'logo' => config('flatpack.ui.logo', null),
                'name' => config('flatpack.ui.name', 'Flatpack'),
                'quickAction' => Flatpack::quickAction(),
                'menu' => array_map(
                    static fn ($item): array => $item->toArray(),
                    Flatpack::menu(),
                ),
                'secondaryMenu' => Flatpack::secondaryMenu(),
                'bottomMenu' => Flatpack::bottomMenu(),
                'showActionShortcutHints' => Flatpack::showActionShortcutHints(),
                'breadcrumbs' => Flatpack::breadcrumbs($request),
                'user' => $request->user() ? FlatpackUser::make($request->user()) : null,
                /** Session flash from redirect->with(...) — surfaced as toasts in the shell layout. */
                'flash' => self::sessionFlashPayload($request),
            ]);
        }

        return $next($request);
    }

    /**
     * Keys commonly flashed from controllers and host action handlers (redirect {@code ->with(...)}).
     *
     * @return array<string, string>
     */
    private static function sessionFlashPayload(Request $request): array
    {
        $session = $request->session();
        $keys = ['success', 'error', 'warning', 'info', 'message'];
        $payload = [];

        foreach ($keys as $key) {
            $value = $session->get($key);
            if (is_string($value) && $value !== '') {
                $payload[$key] = $value;
            }
        }

        return $payload;
    }
}
