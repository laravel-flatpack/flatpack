<?php

declare(strict_types=1);

namespace Flatpack\Support;

/**
 * Collects developer-facing messages when optional composition YAML is sanitized.
 * The `context` string is shown in brackets before each line (e.g. `[posts/form.yaml]`).
 * Prefer {@see CompositionDebugContext} for request flows so callers append via the scoped sink;
 * {@see \Flatpack\Http\FlatpackResponse} reads collected lines into the `composition_debug` prop when
 * {@see config('app.debug')} is true.
 */
final class CompositionDebugLog
{
    /**
     * @var list<string>
     */
    private array $messages = [];

    public function __construct(
        private readonly string $context,
    ) {}

    public function add(string $message): void
    {
        $message = trim($message);
        if ($message === '') {
            return;
        }

        $this->messages[] = $this->context !== ''
            ? "[{$this->context}] {$message}"
            : $message;
    }

    /**
     * @return list<string>
     */
    public function all(): array
    {
        return $this->messages;
    }
}
