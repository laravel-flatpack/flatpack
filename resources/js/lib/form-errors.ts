/** First string message from an Inertia-style error bag (strings or string arrays). */
export function firstErrorMessage(
    errors: Record<string, unknown>,
): string | undefined {
    for (const value of Object.values(errors)) {
        if (typeof value === 'string' && value.trim() !== '') {
            return value;
        }

        if (Array.isArray(value)) {
            const first = value.find(
                (item): item is string =>
                    typeof item === 'string' && item.trim() !== '',
            );
            if (first !== undefined) {
                return first;
            }
        }
    }

    return undefined;
}
