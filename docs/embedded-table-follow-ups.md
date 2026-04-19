# Embedded form table (`type: table`) — follow-ups

Scratch list for later work; not a commitment order.

## Pending

- [ ] **Toolbar action handlers** — Wire embedded table YAML `actions` through to real behavior: implement `onToolbarAction` from the form stack (create/add modals, append row, `router.visit`, etc.). Buttons exist; clicks are still no-ops unless a parent passes a handler.

- [ ] **Optional row-drawer flag** — Today every `TableField` passes `rowDetailDrawer` so row click opens the detail drawer. Add an optional schema flag (e.g. `row_detail_drawer: false`) on `type: table` fields when some tables should not use drawer-on-row-click.

- [ ] **YAML authoring docs** — Document embedded table `actions` (toolbar), relation-backed disable rules until parent is saved, and row-click drawer behavior for authors (`flatpack-yaml-authoring` / entity `form.yaml` examples).

- [ ] **Tests** — Optional Vitest or Playwright coverage: row click opens drawer; save in drawer updates `onValueChange`; toolbar disabled state when relation + unsaved parent.

## Done (context)

- Toolbar strip + normalization (`actions` map/array), relation gate, DataTable toolbar UI.
- Row detail drawer extracted as `DataTableRowDrawerPanel`; embedded tables use `rowDetailDrawer` on `DataTable`.
