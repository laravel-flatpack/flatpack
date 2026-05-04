<?php

declare(strict_types=1);

namespace Flatpack\Navigation;

use Flatpack\Composition\EntityComposition;
use Flatpack\Http\FlatpackRequest;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

/**
 * Builds a linear breadcrumb trail for Flatpack dashboard routes from the current request.
 *
 * @internal
 */
final readonly class BreadcrumbsBuilder
{
    public function __construct(
        private EntityComposition $entityComposition,
        private Repository $config,
    ) {}

    /**
     * @return list<array{label: string, href: string|null}>
     */
    public function forRequest(Request $request): array
    {
        if (! FlatpackRequest::matches($request)) {
            return [];
        }

        $route = $request->route();
        if ($route === null) {
            return [];
        }

        $name = $route->getName();
        if (! is_string($name) || ! str_starts_with($name, 'flatpack.')) {
            return [];
        }

        return match ($name) {
            'flatpack.login' => [
                ['label' => 'Login', 'href' => null],
            ],
            'flatpack.dashboard' => [
                ['label' => $this->dashboardLabel(), 'href' => null],
            ],
            'flatpack.demo.components' => [
                ['label' => $this->dashboardLabel(), 'href' => $this->safeRoute('flatpack.dashboard')],
                ['label' => 'Demo', 'href' => null],
            ],
            'flatpack.entities.index' => $this->entityIndexTrail($this->entitySegment($route->parameter('entity'))),
            'flatpack.entities.create' => $this->entityCreateTrail($this->entitySegment($route->parameter('entity'))),
            'flatpack.entities.edit' => $this->entityEditTrail(
                $this->entitySegment($route->parameter('entity')),
                (string) $route->parameter('record', ''),
            ),
            default => [],
        };
    }

    private function dashboardLabel(): string
    {
        $slug = trim((string) $this->config->get('flatpack.composition.dashboard_entity', 'dashboard'));
        if ($slug === '') {
            $slug = 'dashboard';
        }

        $list = $this->entityComposition->listFor($slug);
        if (is_string($list->name) && $list->name !== '') {
            return $list->name;
        }

        return Str::title(str_replace(['-', '_'], ' ', $slug));
    }

    /**
     * @return list<array{label: string, href: string|null}>
     */
    private function entityIndexTrail(string $entity): array
    {
        if ($entity === '') {
            return [];
        }

        $dashHref = $this->safeRoute('flatpack.dashboard');
        $entityLabel = $this->entityListLabel($entity);

        $trail = [];
        if ($dashHref !== null) {
            $trail[] = ['label' => $this->dashboardLabel(), 'href' => $dashHref];
        }
        $trail[] = ['label' => $entityLabel, 'href' => null];

        return $trail;
    }

    /**
     * @return list<array{label: string, href: string|null}>
     */
    private function entityCreateTrail(string $entity): array
    {
        if ($entity === '') {
            return [];
        }

        $dashHref = $this->safeRoute('flatpack.dashboard');
        $indexHref = $this->safeRoute('flatpack.entities.index', ['entity' => $entity]);

        $trail = [];
        if ($dashHref !== null) {
            $trail[] = ['label' => $this->dashboardLabel(), 'href' => $dashHref];
        }
        if ($indexHref !== null) {
            $trail[] = ['label' => $this->entityListLabel($entity), 'href' => $indexHref];
        }
        $trail[] = ['label' => 'Create', 'href' => null];

        return $trail;
    }

    /**
     * @return list<array{label: string, href: string|null}>
     */
    private function entityEditTrail(string $entity, string $record): array
    {
        if ($entity === '') {
            return [];
        }

        $dashHref = $this->safeRoute('flatpack.dashboard');
        $indexHref = $this->safeRoute('flatpack.entities.index', ['entity' => $entity]);
        $recordSuffix = $record !== '' ? ' #' . $record : '';

        $trail = [];
        if ($dashHref !== null) {
            $trail[] = ['label' => $this->dashboardLabel(), 'href' => $dashHref];
        }
        if ($indexHref !== null) {
            $trail[] = ['label' => $this->entityListLabel($entity), 'href' => $indexHref];
        }
        $trail[] = ['label' => 'Edit' . $recordSuffix, 'href' => null];

        return $trail;
    }

    private function entityListLabel(string $entity): string
    {
        $list = $this->entityComposition->listFor($entity);
        if (is_string($list->name) && $list->name !== '') {
            return $list->name;
        }

        return Str::title(str_replace(['-', '_'], ' ', $entity));
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function safeRoute(string $name, array $parameters = []): ?string
    {
        if (! Route::has($name)) {
            return null;
        }

        try {
            return route($name, $parameters);
        } catch (Throwable) {
            return null;
        }
    }

    private function entitySegment(mixed $entity): string
    {
        if (is_string($entity) && $entity !== '') {
            return $entity;
        }

        return '';
    }
}
