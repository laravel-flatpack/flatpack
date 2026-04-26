/**
 * Focus the primary control for a Flatpack form field (`id` matches {@link FormFieldRenderContext.fieldId}).
 */
export function focusFirstControlForFieldId(fieldId: string): void {
    const root = document.getElementById(fieldId);
    if (!(root instanceof HTMLElement)) {
        return;
    }

    if (
        root instanceof HTMLInputElement ||
        root instanceof HTMLTextAreaElement ||
        root instanceof HTMLSelectElement ||
        root instanceof HTMLButtonElement
    ) {
        root.focus();
        return;
    }

    const inner = root.querySelector<HTMLElement>(
        'input:not([type="hidden"]), textarea, select, button, [role="combobox"], [role="listbox"]',
    );
    if (inner != null) {
        inner.focus();
        return;
    }

    root.setAttribute('tabindex', '-1');
    root.focus();
    root.removeAttribute('tabindex');
}
