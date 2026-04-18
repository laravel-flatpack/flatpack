<?php

declare(strict_types=1);

namespace Flatpack\Support\FormSchema\Pipes;

use Closure;
use Flatpack\Support\FormSchema\FormFieldDefinitionNormalizer;
use Flatpack\Support\FormSchema\FormSchemaPipelineState;

final readonly class NormalizeFormFieldDefinitionsPipe
{
    public function __construct(
        private FormFieldDefinitionNormalizer $fields,
    ) {}

    public function handle(FormSchemaPipelineState $state, Closure $next): mixed
    {
        $fields = $state->schema['fields'] ?? null;
        if (! is_array($fields)) {
            return $next($state);
        }

        $normalizedFields = [];
        foreach ($fields as $fieldId => $fieldDefinition) {
            if (! is_array($fieldDefinition)) {
                $normalizedFields[$fieldId] = $fieldDefinition;

                continue;
            }

            $normalized = $this->fields->normalize($fieldDefinition, (string) $fieldId, $state->log);
            if ($normalized !== null) {
                $normalizedFields[$fieldId] = $normalized;
            }
        }

        $state->schema['fields'] = $normalizedFields;

        return $next($state);
    }
}
