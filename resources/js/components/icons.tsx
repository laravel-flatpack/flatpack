import type { LucideIcon } from 'lucide-react';
import {
    ChartBarIcon,
    DatabaseIcon,
    FileChartColumnIcon,
    FileIcon,
    FolderIcon,
    HelpCircleIcon,
    LayoutDashboardIcon,
    ListIcon,
    SearchIcon,
    Settings2Icon,
    SettingsIcon,
    UsersIcon,
} from 'lucide-react';

export const icons: Record<string, LucideIcon | null> = {
    chartBar: ChartBarIcon,
    dashboard: LayoutDashboardIcon,
    database: DatabaseIcon,
    file: FileIcon,
    fileChartColumn: FileChartColumnIcon,
    folder: FolderIcon,
    helpCircle: HelpCircleIcon,
    list: ListIcon,
    search: SearchIcon,
    settings: SettingsIcon,
    settings2: Settings2Icon,
    users: UsersIcon,
};
