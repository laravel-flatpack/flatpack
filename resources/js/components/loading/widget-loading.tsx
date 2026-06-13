import { Skeleton } from '@/components/ui/skeleton';

/**
 * Lazy field Suspense fallback — matches {@link FormFields}.
 */
export function WidgetLoading(props: {
    type: 'metric' | 'card' | 'status' | 'chart' | 'table';
    label?: string | null;
}) {
    return (
        <>
            {['metric', 'card', 'status', 'chart', 'table'].includes(
                props.type,
            ) ? (
                <Skeleton className="h-25 w-full rounded-3xl" />
            ) : (
                <Skeleton className="h-9 rounded-3xl" />
            )}
        </>
    );
}
