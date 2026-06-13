import * as React from 'react';

type WidgetErrorBoundaryProps = {
    widgetId: string;
    widgetType: string;
    children: React.ReactNode;
};

type WidgetErrorBoundaryState = {
    hasError: boolean;
};

export class WidgetErrorBoundary extends React.Component<
    WidgetErrorBoundaryProps,
    WidgetErrorBoundaryState
> {
    public constructor(props: WidgetErrorBoundaryProps) {
        super(props);
        this.state = { hasError: false };
    }

    public static getDerivedStateFromError(): WidgetErrorBoundaryState {
        return { hasError: true };
    }

    public componentDidCatch(error: unknown): void {
        console.error('Widget render failed', {
            widgetId: this.props.widgetId,
            widgetType: this.props.widgetType,
            error,
        });
    }

    public render(): React.ReactNode {
        if (this.state.hasError) {
            return (
                <div className="rounded-3xl border border-destructive/30 bg-destructive/5 p-4 text-sm">
                    <p className="font-medium text-destructive">
                        Widget failed to render
                    </p>
                    <p className="mt-1 text-muted-foreground">
                        id: {this.props.widgetId} - type:{' '}
                        {this.props.widgetType}
                    </p>
                </div>
            );
        }

        return this.props.children;
    }
}
