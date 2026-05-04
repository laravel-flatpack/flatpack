import type { FieldSpanNamed } from '@/types/form-fields';

export type FieldSpanContext = 'page' | 'repeater' | 'drawer' | 'dashboard';

const NAMED: readonly FieldSpanNamed[] = [
    'full',
    'half',
    'two_thirds',
    'third',
    'quarter',
];

const NAMED_SET = new Set<string>(NAMED);

/** Fraction aliases in YAML → canonical named span (matches PHP {@see \Flatpack\Schema\Forms\Normalization\FieldSpanCanonicalizer}). */
const FRACTION_TO_NAMED: Record<string, FieldSpanNamed> = {
    '1/2': 'half',
    '2/3': 'two_thirds',
    '1/3': 'third',
    '1/4': 'quarter',
};

/**
 * Resolves author input (named or fraction string) to the canonical named token.
 */
export function canonicalizeSpan(value: unknown): FieldSpanNamed | undefined {
    if (typeof value !== 'string') {
        return undefined;
    }
    const trimmed = value.trim();
    if (trimmed === '') {
        return undefined;
    }
    if (NAMED_SET.has(trimmed)) {
        return trimmed as FieldSpanNamed;
    }

    return FRACTION_TO_NAMED[trimmed];
}

/**
 * Tailwind grid column span classes for the given layout context.
 * When {@code span} is omitted: full-width on form page / repeater; drawer always full;
 * dashboard returns undefined so callers can fall back to type-based defaults.
 */
export function spanClassFor(
    span: FieldSpanNamed | undefined,
    context: FieldSpanContext,
): string | undefined {
    if (context === 'drawer') {
        return 'col-span-full';
    }

    if (span === undefined) {
        if (context === 'dashboard') {
            return undefined;
        }

        return 'col-span-full';
    }

    if (context === 'dashboard') {
        switch (span) {
            case 'full':
                return 'col-span-1 md:col-span-2 xl:col-span-4';
            case 'half':
                return 'col-span-1 xl:col-span-2';
            case 'two_thirds':
                return 'col-span-1 xl:col-span-3';
            case 'third':
            case 'quarter':
                return 'col-span-1 xl:col-span-1';
            default:
                return 'col-span-1';
        }
    }

    if (context === 'repeater') {
        switch (span) {
            case 'full':
                return 'col-span-full';
            case 'half':
            case 'two_thirds':
                return 'md:col-span-1 2xl:col-span-2';
            case 'third':
            case 'quarter':
                return 'md:col-span-1 2xl:col-span-1';
            default:
                return 'col-span-full';
        }
    }

    // page
    switch (span) {
        case 'full':
            return 'col-span-full';
        case 'half':
            return 'md:col-span-1 lg:col-span-2 2xl:col-span-3';
        case 'two_thirds':
            return 'md:col-span-1 lg:col-span-3 2xl:col-span-3';
        case 'third':
            return 'md:col-span-1 lg:col-span-1 2xl:col-span-2';
        case 'quarter':
            return 'md:col-span-1 lg:col-span-1 2xl:col-span-1';
        default:
            return 'col-span-full';
    }
}
