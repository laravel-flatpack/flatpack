<?php

declare(strict_types=1);

namespace Flatpack\Composition;

final class CompositionPathGuard
{
    private const string SAFE_SEGMENT_PATTERN = '/^[A-Za-z0-9_-]+$/';

    public static function isSafeSegment(string $value): bool
    {
        return $value !== '' && preg_match(self::SAFE_SEGMENT_PATTERN, $value) === 1;
    }

    public static function resolveBasePath(string $basePath): ?string
    {
        $resolved = realpath($basePath);

        if ($resolved === false || ! is_dir($resolved)) {
            return null;
        }

        return $resolved;
    }

    public static function resolveContainedEntityPath(string $baseRealPath, string $entity): ?string
    {
        $resolved = realpath($baseRealPath . DIRECTORY_SEPARATOR . $entity);

        if ($resolved === false || ! is_dir($resolved)) {
            return null;
        }

        return self::isPathContained($baseRealPath, $resolved)
            ? $resolved
            : null;
    }

    private static function isPathContained(string $basePath, string $candidatePath): bool
    {
        $normalizedBasePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $normalizedCandidatePath = rtrim($candidatePath, DIRECTORY_SEPARATOR);

        return $normalizedCandidatePath === $normalizedBasePath
            || str_starts_with($normalizedCandidatePath, $normalizedBasePath . DIRECTORY_SEPARATOR);
    }
}
