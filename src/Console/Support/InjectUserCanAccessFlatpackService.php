<?php

declare(strict_types=1);

namespace Flatpack\Console\Support;

use Illuminate\Contracts\Config\Repository;
use ReflectionClass;

final readonly class InjectUserCanAccessFlatpackService
{
    public function __construct(
        private Repository $config,
    ) {}

    /**
     * @return class-string
     */
    public function resolveUserModelClass(): string
    {
        $model = $this->config->get('auth.providers.users.model', 'App\\Models\\User');

        return is_string($model) && $model !== '' ? $model : 'App\\Models\\User';
    }

    public function resolveUserModelPath(): ?string
    {
        $class = $this->resolveUserModelClass();

        if (! class_exists($class)) {
            return null;
        }

        $file = (new ReflectionClass($class))->getFileName();

        return $file !== false ? $file : null;
    }

    public function needsInjection(): bool
    {
        $class = $this->resolveUserModelClass();

        if (! class_exists($class)) {
            return false;
        }

        if (method_exists($class, 'canAccessFlatpack')) {
            return false;
        }

        $path = $this->resolveUserModelPath();

        if ($path === null || ! is_file($path)) {
            return false;
        }

        $source = file_get_contents($path);

        if ($source === false) {
            return false;
        }

        return preg_match('/function\s+canAccessFlatpack\s*\(/', $source) !== 1;
    }

    public function inject(): bool
    {
        if (! $this->needsInjection()) {
            return false;
        }

        $class = $this->resolveUserModelClass();
        $reflection = new ReflectionClass($class);
        $path = $reflection->getFileName();

        if ($path === false) {
            return false;
        }

        /** @var list<string> $lines */
        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return false;
        }

        $indent = $this->detectMethodIndent($lines, $reflection->getStartLine(), $reflection->getEndLine());
        $methodLines = $this->methodLines($indent);
        $insertAt = $reflection->getEndLine() - 1;

        array_splice($lines, $insertAt, 0, $methodLines);

        $written = file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);

        return $written !== false;
    }

    /**
     * @param  list<string>  $lines
     */
    private function detectMethodIndent(array $lines, int $startLine, int $endLine): string
    {
        for ($lineNumber = $startLine; $lineNumber < $endLine; $lineNumber++) {
            $line = $lines[$lineNumber - 1] ?? '';

            if (preg_match('/^(\s+)public\s+function\s+/u', $line, $matches) === 1) {
                return $matches[1];
            }
        }

        return '    ';
    }

    /**
     * @return list<string>
     */
    private function methodLines(string $indent): array
    {
        return [
            '',
            $indent . 'public function canAccessFlatpack(): bool',
            $indent . '{',
            $indent . '    return true; // tighten for your app (role, admin flag, etc.)',
            $indent . '}',
        ];
    }
}
