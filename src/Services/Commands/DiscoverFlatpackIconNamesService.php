<?php

declare(strict_types=1);

namespace Flatpack\Services\Commands;

use Illuminate\Filesystem\Filesystem;

final readonly class DiscoverFlatpackIconNamesService
{
    public function __construct(
        private Filesystem $files,
    ) {}

    /**
     * @return list<string>
     */
    public function discover(): array
    {
        $registryPath = dirname(__DIR__, 3) . '/resources/js/components/lucide-menu-icon-registry.ts';
        if (! $this->files->exists($registryPath)) {
            return ['folder-open'];
        }

        $contents = $this->files->get($registryPath);
        $start = mb_strpos($contents, 'export const flatpackMenuIcons = {');
        $end = mb_strpos($contents, '} as const', $start ?: 0);

        if ($start === false || $end === false) {
            return ['folder-open'];
        }

        $objectBody = mb_substr($contents, $start, $end - $start);
        preg_match_all('/^\s*[\'"]?([a-z0-9-]+)[\'"]?\s*:\s*[A-Za-z_]/m', $objectBody, $matches);

        /** @var list<string> $icons */
        $icons = array_values(array_unique($matches[1]));
        sort($icons);

        return $icons === [] ? ['folder-open'] : $icons;
    }
}
