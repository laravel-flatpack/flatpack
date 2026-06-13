import { useCallback, useEffect, useMemo, useRef } from 'react';
import {
    applyPresetCascade,
    buildInitialPresetBlockedIds,
    buildPresetEdgesBySourceId,
    parsePreset,
} from '@/lib/form-field-preset';
import type { FormFieldEntry } from '@/lib/form-schema';
import type { FormFieldProps } from '@/types/form-fields';

type UseFormFieldPresetsOptions = {
    fields: FormFieldEntry[];
    baselineValues: Record<string, unknown>;
};

/**
 * Tracks preset source/destination behavior for Flatpack forms: auto-fill from a source field
 * until the destination is user-edited or was non-empty when the baseline loaded.
 */
export function useFormFieldPresets({
    fields,
    baselineValues,
}: UseFormFieldPresetsOptions) {
    const fieldsById = useMemo(
        () => new Map(fields.map(({ id, field }) => [id, field])),
        [fields],
    );

    const edgesBySource = useMemo(
        () => buildPresetEdgesBySourceId(fields),
        [fields],
    );

    const userTouchedDestRef = useRef<Set<string>>(new Set());
    const initialBlockedDestRef = useRef<Set<string>>(new Set());

    useEffect(() => {
        userTouchedDestRef.current.clear();
        initialBlockedDestRef.current = buildInitialPresetBlockedIds(
            fields,
            baselineValues,
        );
    }, [baselineValues, fields]);

    const mergeFieldChange = useCallback(
        (
            field: FormFieldProps,
            fieldId: string,
            nextSerializedValue: unknown,
            currentValues: Record<string, unknown>,
        ): Record<string, unknown> => {
            if (parsePreset(field)) {
                userTouchedDestRef.current.add(fieldId);
            }

            const merged: Record<string, unknown> = {
                ...currentValues,
                [fieldId]: nextSerializedValue,
            };

            return applyPresetCascade({
                changedSourceId: fieldId,
                values: merged,
                fieldsById,
                edgesBySource,
                userTouchedDest: userTouchedDestRef.current,
                initialBlockedDest: initialBlockedDestRef.current,
            });
        },
        [fieldsById, edgesBySource],
    );

    return { mergeFieldChange };
}
