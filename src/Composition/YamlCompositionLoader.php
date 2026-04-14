<?php

declare(strict_types=1);

namespace Flatpack\Composition;

use Flatpack\Contracts\Composition\CompositionLoader;
use Flatpack\Contracts\Composition\CompositionNotFoundException;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

final readonly class YamlCompositionLoader implements CompositionLoader
{
    public function __construct(
        private Filesystem $filesystem,
        private string $basePath,
    ) {}

    public function load(string $entity, string $type): array
    {
        $entity = trim($entity);
        $type = trim($type);

        if (! CompositionPathGuard::isSafeSegment($entity) || ! CompositionPathGuard::isSafeSegment($type)) {
            throw CompositionNotFoundException::forEntity($entity, $type);
        }

        $baseRealPath = CompositionPathGuard::resolveBasePath($this->basePath);
        if ($baseRealPath === null) {
            throw CompositionNotFoundException::forEntity($entity, $type, $this->basePath);
        }

        $entityPath = $baseRealPath . DIRECTORY_SEPARATOR . $entity;
        $entityRealPath = CompositionPathGuard::resolveContainedEntityPath($baseRealPath, $entity);
        if ($entityRealPath === null) {
            throw CompositionNotFoundException::forEntity($entity, $type, $entityPath);
        }

        $path = $entityRealPath . DIRECTORY_SEPARATOR . $type . '.yaml';

        if (! $this->filesystem->exists($path)) {
            throw CompositionNotFoundException::forEntity($entity, $type, $path);
        }

        $contents = $this->filesystem->get($path);

        try {
            /** @var array<string, mixed> $parsed */
            $parsed = Yaml::parse($contents) ?? [];
        } catch (ParseException $e) {
            throw new RuntimeException("Invalid YAML composition at {$path}: " . $e->getMessage(), 0, $e);
        }

        return $parsed;
    }
}
