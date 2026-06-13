import { describe, expect, it } from 'vitest';
import { buildDashboardWidgetTableVisitSearch } from '@/lib/dashboard-widget-table-url';

describe('buildDashboardWidgetTableVisitSearch', () => {
    it('merges widget page into existing query and preserves unrelated keys', () => {
        const qs = buildDashboardWidgetTableVisitSearch(
            '?json=true&foo=bar',
            'recent_posts',
            {
                page: 2,
                perPage: 2,
                search: '',
                sorting: { sort_by: null, sort_direction: null },
            },
        );
        expect(qs).toContain('json=true');
        expect(qs).toContain('foo=bar');
        expect(qs).toContain('recent_posts_page=2');
        expect(qs).toContain('recent_posts_limit=2');
    });

    it('drops explicit sort params when sorting is cleared', () => {
        const qs = buildDashboardWidgetTableVisitSearch(
            '?a_sort_by=title&a_sort_dir=asc',
            'a',
            {
                page: 1,
                perPage: 10,
                search: '',
                sorting: { sort_by: null, sort_direction: null },
            },
        );
        expect(qs).not.toContain('_sort_by=');
        expect(qs).not.toContain('_sort_dir=');
    });
});
