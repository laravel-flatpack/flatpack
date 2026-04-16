import type { FlatpackListHeaderAction } from '@/types/pages/flatpack';
import { FlatpackListActions } from './flatpack-list-actions';

type FlatpackListHeaderProps = {
    displayName: string;
    listActions: FlatpackListHeaderAction[];
    onRequestConfirm: (
        action: FlatpackListHeaderAction & { action: string },
    ) => void;
    runListAction: (
        action: FlatpackListHeaderAction & { action: string },
    ) => void | Promise<void>;
};

export function FlatpackListHeader({
    displayName,
    listActions,
    onRequestConfirm,
    runListAction,
}: FlatpackListHeaderProps) {
    return (
        <div className="mb-4 flex h-10 w-full flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
            <h1 className="min-w-0 flex-1 text-2xl font-semibold tracking-tight">
                {displayName}
            </h1>
            <FlatpackListActions
                listActions={listActions}
                onRequestConfirm={onRequestConfirm}
                runListAction={runListAction}
            />
        </div>
    );
}
