<?php

declare(strict_types=1);

namespace Flatpack\Composition;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Resolves {@code sidebar: fragment.yaml} on form compositions by loading YAML from the entity directory.
 */
final readonly class FormSidebarYamlExpander
{
    public function __construct(
        private Filesystem $filesystem,
        private Repository $config,
    ) {}

    /**
     * When {@code sidebar} is a non-empty string, replaces it with the parsed YAML array from disk.
     * Invalid or unsafe paths leave the schema unchanged (caller may log).
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    public function expand(string $entity, array $schema): array
    {
        $sidebar = $schema['sidebar'] ?? null;
        if (! is_string($sidebar)) {
            return $schema;
        }

        $relative = trim($sidebar);
        if ($relative === '') {
            return $schema;
        }

        $parsed = $this->loadRelativeYaml($entity, $relative);
        if ($parsed === null) {
            return $schema;
        }

        $schema['sidebar'] = $parsed;

        return $schema;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadRelativeYaml(string $entity, string $relativePath): ?array
    {
        $entity = trim($entity);
        if ($entity === '' || ! CompositionPathGuard::isSafeSegment($entity)) {
            return null;
        }

        $normalized = trim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        if ($normalized === '' || str_contains($normalized, '..')) {
            return null;
        }

        foreach (explode(DIRECTORY_SEPARATOR, $normalized) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return null;
            }
            if (preg_match('/^[A-Za-z0-9._-]+$/', $segment) !== 1) {
                return null;
            }
        }

        $basePath = rtrim((string) $this->config->get('flatpack.composition.path', base_path('flatpack')), DIRECTORY_SEPARATOR);
        $baseRealPath = CompositionPathGuard::resolveBasePath($basePath);
        if ($baseRealPath === null) {
            return null;
        }

        $entityRealPath = CompositionPathGuard::resolveContainedEntityPath($baseRealPath, $entity);
        if ($entityRealPath === null) {
            return null;
        }

        $fullPath = $entityRealPath . DIRECTORY_SEPARATOR . $normalized;
        if (! $this->filesystem->exists($fullPath) || ! $this->filesystem->isFile($fullPath)) {
            return null;
        }

        $contents = $this->filesystem->get($fullPath);

        try {
            /** @var array<string, mixed>|null $parsed */
            $parsed = Yaml::parse($contents);
        } catch (ParseException $e) {
            throw new RuntimeException("Invalid YAML sidebar fragment at {$fullPath}: " . $e->getMessage(), 0, $e);
        }

        return is_array($parsed) ? $parsed : [];
    }
}
