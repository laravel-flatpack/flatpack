<?php

declare(strict_types=1);

namespace Flatpack\Http\Middleware;

use Closure;
use Flatpack\Contracts\Authorization\FlatpackAuthorizer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureFlatpackAccess
{
    public function __construct(
        private FlatpackAuthorizer $authorizer,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $this->authorizer->canAccessPanel($user)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
