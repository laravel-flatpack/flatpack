import { Skeleton } from '@/components/ui/skeleton';

/**
 * Lazy field Suspense fallback — matches {@link FlatpackFormFields}.
 */
export function WidgetLoading(props: {
    type: 'metric' | 'card' | 'status';
    label: string;
}) {
    return (
        <>
            {['metric', 'card', 'status'].includes(props.type) ? (
                <Skeleton className="h-25 w-full rounded-3xl" />
            ) : (
                <Skeleton className="h-9 rounded-3xl" />
            )}
        </>
    );
}
