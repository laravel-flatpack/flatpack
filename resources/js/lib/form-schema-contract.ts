import { FORM_FIELD_TYPES_CANONICAL } from '@/lib/generated/composition-schema-keys';
import type { FormFieldType } from '@/types/form-fields';

/**
 * Runtime allowlist for normalized `field.type` values ({@link normalizeFields}).
 *
 * Sourced from `composition-schema-keys.ts` (generated from `form.json`, same set as PHP
 * `CompositionSchemaKeys::FORM_FIELD_TYPES_CANONICAL`).
 */
export const SUPPORTED_FORM_FIELD_TYPES: readonly FormFieldType[] =
    FORM_FIELD_TYPES_CANONICAL as readonly FormFieldType[];
