import { describe, expect, it } from 'vitest';
import { hasLucideIcon } from '@/components/icons/icons';

describe('hasLucideIcon', () => {
    it('supports common bulk action icon aliases', () => {
        expect(hasLucideIcon('check')).toBe(true);
        expect(hasLucideIcon('trash')).toBe(true);
        expect(hasLucideIcon('x')).toBe(true);
    });
});
