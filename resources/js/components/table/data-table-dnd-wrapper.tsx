import {
    closestCenter,
    DndContext,
    type DragEndEvent,
    KeyboardSensor,
    MouseSensor,
    TouchSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import { restrictToVerticalAxis } from '@dnd-kit/modifiers';
import * as React from 'react';

export function DataTableDndWrapper({
    children,
    onDragEnd,
}: {
    children: React.ReactNode;
    onDragEnd: (event: DragEndEvent) => void;
}) {
    const dndSensors = useSensors(
        useSensor(MouseSensor, {}),
        useSensor(TouchSensor, {}),
        useSensor(KeyboardSensor, {}),
    );
    const dndId = React.useId();

    return (
        <DndContext
            id={dndId}
            collisionDetection={closestCenter}
            modifiers={[restrictToVerticalAxis]}
            onDragEnd={onDragEnd}
            sensors={dndSensors}
        >
            {children}
        </DndContext>
    );
}
