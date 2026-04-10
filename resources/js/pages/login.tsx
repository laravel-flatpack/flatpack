import { Form, Head, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Field, FieldGroup, FieldTitle } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type { FlatpackPageProps } from '@/types/flatpack';

const LoginForm = ({ loginAction }: { loginAction: string }) => (
    <Form
        action={loginAction}
        method="post"
        resetOnSuccess={['password']}
        className="p-6 md:p-8"
    >
        {({ processing, errors }) => (
            <>
                <FieldGroup>
                    <div className="flex flex-col items-center gap-2 text-center">
                        <h1 className="text-2xl font-bold">Welcome back</h1>
                        <p className="text-balance text-muted-foreground">
                            Login to your dashboard.
                        </p>
                    </div>
                    <Field>
                        <FieldTitle id="email-label">Email address</FieldTitle>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autoFocus
                            tabIndex={1}
                            autoComplete="email"
                            placeholder="email@example.com"
                            aria-labelledby="email-label"
                        />
                        <InputError message={errors.email} />
                    </Field>
                    <Field>
                        <FieldTitle id="password-label">Password</FieldTitle>
                        <PasswordInput
                            id="password"
                            name="password"
                            required
                            tabIndex={2}
                            autoComplete="current-password"
                            placeholder="Password"
                            aria-labelledby="password-label"
                        />
                        <InputError message={errors.password} />
                    </Field>
                    <div className="flex items-center space-x-3">
                        <Checkbox
                            id="remember"
                            name="remember"
                            tabIndex={3}
                            aria-labelledby="remember-label"
                        />
                        <FieldTitle
                            id="remember-label"
                            className="font-normal text-muted-foreground"
                        >
                            Remember me
                        </FieldTitle>
                    </div>
                    <Field className="mt-4">
                        <Button
                            type="submit"
                            size="lg"
                            tabIndex={3}
                            disabled={processing}
                            data-test="login-button"
                        >
                            {processing && <Spinner />}
                            Login
                        </Button>
                    </Field>
                </FieldGroup>
            </>
        )}
    </Form>
);

export default function FlatpackLogin() {
    const {
        props: { flatpack },
    } = usePage<FlatpackPageProps>();
    const loginStoreRoute = flatpack.pages.login;

    return (
        <>
            <Head title="Log in" />
            <div className="flex min-h-svh flex-col items-center justify-center bg-muted p-6 md:p-10">
                <div className="w-full max-w-sm md:max-w-md">
                    <div className="flex flex-col gap-6">
                        <Card className="overflow-hidden p-0">
                            <CardContent className="grid p-0">
                                <LoginForm loginAction={loginStoreRoute} />
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}
