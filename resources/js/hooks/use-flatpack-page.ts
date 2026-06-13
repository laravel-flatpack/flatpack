import { useCompositionDebugLog } from '@/hooks/use-composition-debug-log';

export function useFlatpackPage(props: { composition_debug?: string[] }): void {
    useCompositionDebugLog(props.composition_debug);
}
