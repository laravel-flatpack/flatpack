import { Skeleton } from '@/components/ui/skeleton';
import type { FormFieldProps } from '@/types/form-fields';

/**
 * Lazy field Suspense fallback — matches {@link FormFields}.
 */
export function FieldLoading(props: FormFieldProps) {
    return (
        <>
            {props.label ? (
                <Skeleton className="mb-4 h-4 w-32 rounded-full" />
            ) : null}
            {['textarea', 'rich-text', 'block-editor'].includes(props.type) ? (
                <Skeleton className="h-25 w-full rounded-3xl" />
            ) : (
                <Skeleton className="h-9 rounded-3xl" />
            )}
            {props.helperText ? (
                <Skeleton className="h-4 w-40 rounded-full" />
            ) : null}
        </>
    );
}
