import { AlertCircle } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

export type FormTopError = { message: string };

export function FormTopErrors({ errors }: { errors: FormTopError[] }) {
    if (errors.length === 0) {
        return null;
    }

    return (
        <Alert variant="destructive">
            <AlertCircle />
            <AlertTitle>Error</AlertTitle>
            <AlertDescription>
                {errors.length === 1 ? (
                    errors[0]?.message
                ) : (
                    <ul className="ml-4 flex list-disc flex-col gap-1">
                        {errors.map(
                            (error) =>
                                error?.message && (
                                    <li key={error.message}>{error.message}</li>
                                ),
                        )}
                    </ul>
                )}
            </AlertDescription>
        </Alert>
    );
}
