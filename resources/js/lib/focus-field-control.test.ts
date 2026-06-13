import { describe, expect, it } from 'vitest';
import { focusFirstControlForFieldId } from '@/lib/focus-field-control';

describe('focusFirstControlForFieldId', () => {
    it('focuses a nested input inside a field root', () => {
        document.body.innerHTML = `
            <div id="bio">
                <input id="bio-inner" type="text" />
            </div>
        `;
        const inner = document.getElementById('bio-inner');
        expect(inner).toBeInstanceOf(HTMLInputElement);
        (inner as HTMLInputElement).blur();
        focusFirstControlForFieldId('bio');
        expect(document.activeElement).toBe(inner);
    });
});
