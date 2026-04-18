<?php

declare(strict_types=1);

namespace Flatpack\Support\FormSchema\Pipes;

use Closure;
use Flatpack\Support\FormSchema\FormFieldDefinitionNormalizer;
use Flatpack\Support\FormSchema\FormSchemaPipelineState;

/**
 * Second pass: drop preset when source field is missing or self-referential (requires full field set).
 */
final readonly class StripInvalidFormPresetsPipe
{
    public function __construct(
        private FormFieldDefinitionNormalizer $fieldNormalizer,
    ) {}

    public function handle(FormSchemaPipelineState $state, Closure $next): mixed
    {
        $fields = $state->schema['fields'] ?? null;
        if (! is_array($fields)) {
            return $next($state);
        }

        $ids = $this->fieldIdsFromDefinitions($fields);

        foreach ($fields as $yamlKey => $definition) {
            if (! is_array($definition)) {
                continue;
            }

            if (! isset($definition['preset']) || ! is_array($definition['preset'])) {
                continue;
            }

            $label = $this->fieldNormalizer->fieldDisplayLabel($definition, (string) $yamlKey);
            $destId = trim((string) ($definition['id'] ?? $yamlKey));
            $source = trim((string) ($definition['preset']['field'] ?? ''));

            if ($source === '') {
                $state->log?->add(sprintf('Form field "%s": removed preset (missing source field).', $label));
                unset($fields[$yamlKey]['preset']);

                continue;
            }
            if ($source === $destId) {
                $state->log?->add(sprintf('Form field "%s": removed preset (cannot reference itself).', $label));
                unset($fields[$yamlKey]['preset']);

                continue;
            }
            if (! isset($ids[$source])) {
                $state->log?->add(sprintf('Form field "%s": removed preset (source field "%s" does not exist).', $label, $source));
                unset($fields[$yamlKey]['preset']);

                continue;
            }
        }

        $state->schema['fields'] = $fields;

        return $next($state);
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, true>
     */
    private function fieldIdsFromDefinitions(array $fields): array
    {
        $ids = [];
        foreach ($fields as $yamlKey => $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $id = trim((string) ($definition['id'] ?? $yamlKey));
            if ($id !== '') {
                $ids[$id] = true;
            }
        }

        return $ids;
    }
}
