import type { Value } from 'platejs';

const emptyDoc = [{ type: 'p', children: [{ text: '' }] }] as Value;

function isTextLeafCandidate(node: Record<string, unknown>): boolean {
    return Object.hasOwn(node, 'text') && !Object.hasOwn(node, 'children');
}

function sanitizeDescendant(node: unknown): unknown {
    if (typeof node !== 'object' || node === null) {
        return { text: '' };
    }

    const n = node as Record<string, unknown>;

    if (isTextLeafCandidate(n)) {
        const raw = n.text;
        let text: string;
        if (typeof raw === 'string') {
            text = raw;
        } else if (raw === null || raw === undefined) {
            text = '';
        } else {
            text = String(raw);
        }
        return { ...n, text };
    }

    const rawChildren = n.children;
    const childrenInput = Array.isArray(rawChildren) ? rawChildren : [];
    const children =
        childrenInput.length > 0
            ? childrenInput.map(sanitizeDescendant)
            : [{ text: '' }];

    return { ...n, children };
}

/**
 * Coerces API / DB JSON into valid Slate descendants so Plate can render.
 * Fixes `text: null`, missing `children`, and other shapes that break slate-react
 * (e.g. splitDecorationsByChild expects element.children to be iterable).
 */
export function sanitizePlateValue(value: Value): Value {
    if (!Array.isArray(value) || value.length === 0) {
        return emptyDoc;
    }
    return value.map((node) => sanitizeDescendant(node)) as Value;
}
