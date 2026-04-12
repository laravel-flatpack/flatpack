import { cleanup, render, screen } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const hoisted = vi.hoisted(() => ({
    form: {
        processing: false,
        errors: {} as Record<string, string>,
    },
    usePage: vi.fn(),
}));

vi.mock('@inertiajs/react', () => ({
    usePage: () => hoisted.usePage(),
    Head: ({ title }: { title: string }) => <title>{title}</title>,
    Form: ({
        action,
        method,
        children,
        className,
    }: {
        action: string;
        method: string;
        children: (state: {
            processing: boolean;
            errors: Record<string, string>;
        }) => import('react').ReactNode;
        className?: string;
    }) => (
        <form action={action} method={method} className={className}>
            {typeof children === 'function'
                ? children({
                      processing: hoisted.form.processing,
                      errors: hoisted.form.errors,
                  })
                : children}
        </form>
    ),
}));

import FlatpackLogin from '@/pages/login';

function mockFlatpackPage() {
    hoisted.usePage.mockReturnValue({
        props: {
            flatpack: {
                menu: null,
                pages: {
                    dashboard: '/dashboard',
                    login: '/auth/login',
                    logout: '/auth/logout',
                },
            },
        },
    });
}

describe('FlatpackLogin', () => {
    beforeEach(() => {
        hoisted.form.processing = false;
        hoisted.form.errors = {};
        mockFlatpackPage();
    });

    afterEach(() => {
        cleanup();
    });

    it('sets the document title', () => {
        render(<FlatpackLogin />);
        expect(document.querySelector('title')?.textContent).toBe('Log in');
    });

    it('posts the form to the flatpack login route', () => {
        render(<FlatpackLogin />);
        const form = document.querySelector('form');
        expect(form).not.toBeNull();
        expect(form).toHaveAttribute('action', '/auth/login');
        expect(form).toHaveAttribute('method', 'post');
    });

    it('renders email and password fields with expected accessibility', () => {
        render(<FlatpackLogin />);
        expect(
            screen.getByRole('textbox', { name: 'Email address' }),
        ).toBeInTheDocument();
        expect(screen.getByLabelText('Password')).toBeInTheDocument();
        expect(
            screen.getByRole('checkbox', { name: 'Remember me' }),
        ).toBeInTheDocument();
    });

    it('shows validation errors from the form render props', () => {
        hoisted.form.errors = {
            email: 'Email is required.',
            password: 'Invalid credentials.',
        };
        render(<FlatpackLogin />);
        expect(screen.getByText('Email is required.')).toBeInTheDocument();
        expect(screen.getByText('Invalid credentials.')).toBeInTheDocument();
    });

    it('disables submit while processing', () => {
        hoisted.form.processing = true;
        render(<FlatpackLogin />);
        expect(screen.getByRole('button', { name: /login/i })).toBeDisabled();
    });
});
