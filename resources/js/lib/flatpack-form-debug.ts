import { firstErrorMessage } from '@/lib/form-errors';

const STORAGE_KEY = 'flatpack_debug_form_save';

/**
 * Form save / validation failures log to the console when this returns true.
 *
 * - Always in Vite `import.meta.env.DEV`
 * - In production, set: `localStorage.setItem('flatpack_debug_form_save', '1')` then refresh
 */
export function isFlatpackFormSaveDebugEnabled(): boolean {
    if (import.meta.env.DEV) {
        return true;
    }
    try {
        return globalThis.localStorage?.getItem(STORAGE_KEY) === '1';
    } catch {
        return false;
    }
}

/** Shallow, size-bounded summary for console logging (avoids huge payloads). */
export function summarizeFormValuesForDebug(
    values: Record<string, unknown>,
    opts?: { maxTableRows?: number },
): Record<string, unknown> {
    const maxTableRows = opts?.maxTableRows ?? 5;
    const out: Record<string, unknown> = {};
    for (const [k, v] of Object.entries(values)) {
        if (Array.isArray(v)) {
            const rows = v;
            out[k] = {
                _type: 'array' as const,
                length: rows.length,
                sample: rows
                    .slice(0, maxTableRows)
                    .map((row) => summarizeTableRowForDebug(row)),
            };
        } else if (v !== null && typeof v === 'object' && !Array.isArray(v)) {
            out[k] = {
                _type: 'object' as const,
                keys: Object.keys(v as object).slice(0, 40),
            };
        } else {
            out[k] = v;
        }
    }
    return out;
}

function summarizeTableRowForDebug(row: unknown): Record<string, unknown> {
    if (row === null || typeof row !== 'object' || Array.isArray(row)) {
        return { _raw: String(row) };
    }
    const o = row as Record<string, unknown>;
    const keys = Object.keys(o);
    const sample: Record<string, unknown> = {};
    for (const key of keys.slice(0, 30)) {
        const val = o[key];
        if (val !== null && typeof val === 'object' && !Array.isArray(val)) {
            sample[key] = {
                _type: 'nested' as const,
                keys: Object.keys(val as object).slice(0, 20),
            };
        } else if (Array.isArray(val)) {
            sample[key] = { _type: 'array' as const, length: val.length };
        } else {
            sample[key] = val;
        }
    }
    if (keys.length > 30) {
        sample._truncated = `+${keys.length - 30} more keys`;
    }
    return sample;
}

function inertiaErrorString(value: unknown): string | undefined {
    if (typeof value === 'string' && value.trim() !== '') {
        return value;
    }
    if (Array.isArray(value)) {
        const s = value.find(
            (item): item is string =>
                typeof item === 'string' && item.trim() !== '',
        );
        return s;
    }
    return undefined;
}

type FormSaveErrorContext = {
    phase: 'client_validation' | 'inertia_on_error' | 'named_action_error';
    entity: string;
    record: string | null;
    mode: 'create' | 'edit';
    formActionId?: string;
    submitUrl?: string;
    errors: Record<string, unknown>;
    values: Record<string, unknown>;
    /** Free-form, e.g. from router when available. */
    extra?: Record<string, unknown>;
};

export function logFlatpackFormSaveError(context: FormSaveErrorContext): void {
    if (!isFlatpackFormSaveDebugEnabled()) {
        return;
    }
    const primary = firstErrorMessage(context.errors);
    const keys = Object.keys(context.errors);
    // Always surface the message + bag outside a collapsed group (easy to copy from console).
    console.error(
        `[FLATPACK] form ${context.phase} — primary message:`,
        primary ?? '(none — check keys below)',
    );
    const underlyingClass = inertiaErrorString(
        context.errors.flatpack_exception,
    );
    const underlyingMessage = inertiaErrorString(
        context.errors.flatpack_exception_message,
    );
    if (underlyingClass !== undefined || underlyingMessage !== undefined) {
        console.error('[FLATPACK] underlying exception (from server):', {
            class: underlyingClass,
            message: underlyingMessage,
        });
    }
    console.error('[FLATPACK] Inertia error bag (copy this):', {
        keys,
        raw: context.errors,
    });
    let json: string | undefined;
    try {
        json = JSON.stringify(context.errors, null, 2);
    } catch {
        json = undefined;
    }
    if (json !== undefined) {
        console.error(`[FLATPACK] error bag JSON:\n${json}`);
    }

    const title = `[FLATPACK] form ${context.phase} (details)`;
    // Expanded group so DevTools does not hide rows until you click.
    console.group(title, context.entity, context.mode, context.record);
    console.log('Inertia/validation error bag (full):', context.errors);
    console.log('Submitted values (summary, table fields show row shapes):', {
        form_action_id: context.formActionId,
        submitUrl: context.submitUrl,
    });
    console.log('values summary:', summarizeFormValuesForDebug(context.values));
    if (context.extra !== undefined && Object.keys(context.extra).length > 0) {
        console.log('extra:', context.extra);
    }
    if (underlyingClass === undefined && underlyingMessage === undefined) {
        console.log(
            'Tip: set FLATPACK_LOG_FORM_SAVE_FAILURES=true or APP_DEBUG=true so the 422 bag includes flatpack_exception and flatpack_exception_message.',
        );
    }
    console.log(
        'Server: storage/logs/laravel.log (report() + optional FormController warning when log_form_save_failures).',
    );
    console.groupEnd();
}
