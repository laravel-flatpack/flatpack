/** Shown while a Plate-based editor chunk is loading (rich text / block editor). */
export function PlateEditorFallback() {
    return (
        <div
            className="flex min-h-[220px] w-full items-center justify-center rounded-md border border-dashed border-border bg-muted/20 text-sm text-muted-foreground"
            aria-hidden
        >
            Loading editor…
        </div>
    );
}
