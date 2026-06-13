import { route } from '@/lib/route';

type RelationComboboxRemoteContext =
    | {
          kind: 'field';
          entity: string;
          fieldId: string;
      }
    | {
          kind: 'embedded-table-column';
          entity: string;
          tableFieldId: string;
          columnId: string;
      }
    | {
          kind: 'widget-table-column';
          widgetId: string;
          columnId: string;
      };

export function relationComboboxRemoteProps(
    context: RelationComboboxRemoteContext,
    portalContainer?: HTMLElement | null,
): Record<string, unknown> {
    if (context.kind === 'field') {
        return {
            remote: true,
            remoteEndpoint: route('flatpack.entities.relation-options', {
                entity: context.entity,
            }),
            remoteFieldId: context.fieldId,
            portalContainer,
        };
    }

    if (context.kind === 'embedded-table-column') {
        return {
            remote: true,
            remoteEndpoint: route(
                'flatpack.entities.embedded-table-relation-options',
                {
                    entity: context.entity,
                },
            ),
            remoteFieldParamKey: null,
            remoteSearchParamKey: 'q',
            remoteBaseParams: {
                table_field: context.tableFieldId,
                column_id: context.columnId,
            },
            portalContainer,
        };
    }

    return {
        remote: true,
        remoteEndpoint: route('flatpack.dashboard.widgets.relation-options', {
            widget: context.widgetId,
        }),
        remoteFieldParamKey: null,
        remoteSearchParamKey: 'q',
        remoteBaseParams: {
            column_id: context.columnId,
        },
        portalContainer,
    };
}
