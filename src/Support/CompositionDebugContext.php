<?php

declare(strict_types=1);

namespace Flatpack\Support;

/**
 * Request-scoped sink for composition YAML sanitation messages when {@see config('app.debug')} is true.
 * Call {@see self::activate()} once per Flatpack page so normalizers and resolvers can append via
 * {@see self::sink()} / {@see self::add()} without threading {@see CompositionDebugLog} through method signatures.
 * {@see \Flatpack\Http\FlatpackResponse::inertia()} reads {@see self::lines()} for the `composition_debug` prop.
 */
final class CompositionDebugContext
{
    private bool $activated = false;

    private ?CompositionDebugLog $log = null;

    public static function resolveOptional(?CompositionDebugLog $explicit): ?CompositionDebugLog
    {
        if ($explicit !== null) {
            return $explicit;
        }

        return app(self::class)->sink();
    }

    public function activate(string $context): void
    {
        if ($this->activated) {
            return;
        }

        $this->activated = true;

        if (config('app.debug')) {
            $this->log = new CompositionDebugLog($context);
        }
    }

    public function sink(): ?CompositionDebugLog
    {
        return $this->log;
    }

    /**
     * @return list<string>
     */
    public function lines(): array
    {
        return $this->log?->all() ?? [];
    }

    public function add(string $message): void
    {
        $this->sink()?->add($message);
    }
}
