<?php

declare(strict_types=1);

namespace Flatpack\Support;

/**
 * Collects developer-facing messages when optional composition YAML is sanitized.
 * The `context` string is shown in brackets before each line (e.g. `[posts/form.yaml]`).
 * In HTTP requests, {@see \Flatpack\Http\FlatpackResponse} supplies a path-style context when
 * {@see config('app.debug')} is true so it matches the YAML file under the flatpack directory.
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
