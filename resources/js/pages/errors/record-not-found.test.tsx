import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const { routeMock } = vi.hoisted(() => ({
    routeMock: vi.fn(
        (name: string, params: { entity: string }) =>
            `/${name}/${params.entity}`,
    ),
}));

vi.mock('ziggy-js', () => ({
    route: routeMock,
}));

vi.mock('@inertiajs/react', () => ({
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    Link: ({ children, href }: { children: React.ReactNode; href: string }) => (
        <a href={href}>{children}</a>
    ),
}));

import FlatpackRecordNotFoundPage from '@/pages/errors/record-not-found';

describe('FlatpackRecordNotFoundPage', () => {
    beforeEach(() => {
        routeMock.mockClear();
    });

    afterEach(() => {
        cleanup();
    });

    it('renders copy from entityName, sets the head title, and links to the list route for entity', () => {
        render(
            <FlatpackRecordNotFoundPage entity="posts" entityName="Posts" />,
        );

        expect(globalThis.document.querySelector('title')?.textContent).toBe(
            'Record not found',
        );
        expect(
            screen.getByRole('heading', { name: 'Record not found' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('The Posts you are looking for does not exist.', {
                exact: true,
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                /We could not find that Posts\. It may have been removed, or you might not have permission to view it\./,
            ),
        ).toBeInTheDocument();

        expect(
            screen.getByRole('link', { name: 'Back to Posts list' }),
        ).toHaveAttribute('href', '/flatpack.entities.index/posts');

        expect(routeMock).toHaveBeenCalledWith('flatpack.entities.index', {
            entity: 'posts',
        });
    });
});
