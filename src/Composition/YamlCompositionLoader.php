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
        $path = rtrim($this->basePath, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $entity
            . DIRECTORY_SEPARATOR
            . $type . '.yaml';

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
