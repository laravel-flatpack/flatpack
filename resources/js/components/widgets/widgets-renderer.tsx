import { useEffect, useMemo, useState } from 'react';
import type { MenuIconName } from '@/components/icons/lucide-menu-icon-registry';
import { menuIcons } from '@/components/icons/lucide-menu-icon-registry';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { WidgetErrorBoundary } from '@/components/widgets/widget-error-boundary';
import { cn } from '@/lib/utils';
import { renderWidgetByType, widgetGridSpanClass } from '@/lib/widget-trigger';
import type {
    FlatpackWidget,
    FlatpackWidgetsCompositionSchema,
    FlatpackWidgetTabPanelLayout,
} from '@/types/widgets-composition';

type WidgetsRendererProps = {
    widgets?: FlatpackWidgetsCompositionSchema['widgets'];
    tabPanels?: FlatpackWidgetTabPanelLayout[];
    className?: string;
};

type WidgetEntry = {
    id: string;
    widget: FlatpackWidget;
};

export function WidgetsRenderer({
    widgets,
    tabPanels,
    className,
}: WidgetsRendererProps) {
    const entries = useMemo<WidgetEntry[]>(
        () =>
            Object.entries(widgets ?? {}).map(([id, widget]) => ({
                id,
                widget,
            })),
        [widgets],
    );
    const entryById = useMemo(
        () => new Map(entries.map((entry) => [entry.id, entry])),
        [entries],
    );

    const { unassignedEntries, tabBlocks } = useMemo(() => {
        if (tabPanels === undefined || tabPanels.length === 0) {
            return {
                unassignedEntries: entries,
                tabBlocks: [] as Array<{
                    panelId: string;
                    entries: WidgetEntry[];
                }>,
            };
        }

        const assigned = new Set<string>();
        for (const panel of tabPanels) {
            for (const widgetId of panel.widget_ids) {
                assigned.add(widgetId);
            }
        }

        return {
            unassignedEntries: entries.filter(
                (entry) => !assigned.has(entry.id),
            ),
            tabBlocks: tabPanels.map((panel) => ({
                panelId: panel.id,
                entries: panel.widget_ids
                    .map((widgetId) => entryById.get(widgetId))
                    .filter(
                        (entry): entry is WidgetEntry => entry !== undefined,
                    ),
            })),
        };
    }, [entries, entryById, tabPanels]);

    const [activeTab, setActiveTab] = useState(() => tabPanels?.[0]?.id ?? '');

    useEffect(() => {
        if (tabPanels === undefined || tabPanels.length === 0) {
            return;
        }
        setActiveTab((current) =>
            tabPanels.some((panel) => panel.id === current)
                ? current
                : (tabPanels[0]?.id ?? ''),
        );
    }, [tabPanels]);

    const renderWidgetGrid = (subset: WidgetEntry[]) => (
        <div
            className={cn(
                'grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4',
                className,
            )}
        >
            {subset.map((entry) => (
                <div
                    key={entry.id}
                    className={widgetGridSpanClass(entry.widget)}
                    data-widget-id={entry.id}
                >
                    <WidgetErrorBoundary
                        widgetId={entry.id}
                        widgetType={entry.widget.type}
                    >
                        {renderWidgetByType(entry.id, entry.widget)}
                    </WidgetErrorBoundary>
                </div>
            ))}
        </div>
    );

    if (tabPanels === undefined || tabPanels.length === 0) {
        return renderWidgetGrid(entries);
    }

    return (
        <div className="flex flex-col gap-6">
            {unassignedEntries.length > 0
                ? renderWidgetGrid(unassignedEntries)
                : null}
            <Tabs
                value={activeTab}
                onValueChange={setActiveTab}
                className="w-full"
            >
                <div className="w-full overflow-x-auto">
                    <TabsList
                        variant="line"
                        className="inline-flex w-max min-w-max flex-nowrap justify-start"
                    >
                        {tabPanels.map((panel) => {
                            const Icon =
                                panel.icon != null && panel.icon in menuIcons
                                    ? menuIcons[panel.icon as MenuIconName]
                                    : null;
                            return (
                                <TabsTrigger
                                    key={panel.id}
                                    value={panel.id}
                                    className="flex-none"
                                >
                                    {Icon != null ? (
                                        <Icon
                                            data-icon="inline-start"
                                            className="size-4"
                                        />
                                    ) : null}
                                    {panel.label}
                                </TabsTrigger>
                            );
                        })}
                    </TabsList>
                </div>
                {tabBlocks.map((block) => (
                    <TabsContent
                        key={block.panelId}
                        value={block.panelId}
                        className="pt-4 px-2"
                    >
                        {renderWidgetGrid(block.entries)}
                    </TabsContent>
                ))}
            </Tabs>
        </div>
    );
}
