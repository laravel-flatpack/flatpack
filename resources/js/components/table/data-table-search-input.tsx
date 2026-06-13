import { SearchIcon } from 'lucide-react';
import { DATA_TABLE_SEARCH_PLACEHOLDER } from '@/components/table/data-table-constants';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type DataTableSearchInputProps = {
    id: string;
    value: string;
    onChange: (value: string) => void;
};

export function DataTableSearchInput({
    id,
    value,
    onChange,
}: DataTableSearchInputProps) {
    return (
        <div className="relative w-full max-w-md">
            <Label htmlFor={`${id}-search`} className="sr-only">
                Search rows
            </Label>
            <SearchIcon
                className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden
            />
            <Input
                id={`${id}-search`}
                type="search"
                value={value}
                onChange={(event) => onChange(event.target.value)}
                placeholder={DATA_TABLE_SEARCH_PLACEHOLDER}
                className="h-8 pl-8"
                autoComplete="off"
            />
        </div>
    );
}
