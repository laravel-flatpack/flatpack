<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Http\FlatpackResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

use function fake;

final class DemoController
{
    public function index(Request $request): Response|JsonResponse
    {
        $catalogId = $this->normalizeCatalogId($request->query('catalog'));

        return FlatpackResponse::inertia('docs/catalog', [
            'catalogId' => $catalogId,
            'query' => $request->query(),
            'document' => $this->buildDocument($catalogId),
        ]);
    }

    private function normalizeCatalogId(mixed $value): string
    {
        if (is_string($value)) {
            $normalized = mb_strtolower(trim($value));
            if (in_array($normalized, ['fields', 'widgets'], true)) {
                return $normalized;
            }
        }

        return 'all';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildDocument(string $id): array
    {
        $fields = $this->fieldsCatalog();
        $widgets = $this->widgetsCatalog();

        return [
            'id' => $id,
            'title' => match ($id) {
                'fields' => 'Form Fields',
                'widgets' => 'Widgets',
                default => 'Components',
            },
            'description' => match ($id) {
                'fields' => 'Form fields available to Flatpack compositions. Use <code>?type=text</code> to embed a single field.',
                'widgets' => 'Dashboard and detail-page widgets. Use <code>?catalog=widgets&type=metric</code> to embed a single widget.',
                default => 'Full catalog of Flatpack form fields and widgets. Use <code>?type=</code> to embed a single component.',
            },
            'meta' => [
                'fieldCount' => count($fields),
                'widgetCount' => count($widgets),
            ],
            'fields' => $id === 'widgets' ? [] : $fields,
            'widgets' => $id === 'fields' ? [] : $widgets,
        ];
    }

    /**
     * Component docs widgets catalog for the `/demo` page.
     *
     * @return list<array<string, mixed>>
     */
    private function widgetsCatalog(): array
    {
        return [
            [
                'id' => 'total_revenue',
                'title' => 'Metric card (currency format)',
                'description' => 'Metric widget with a currency value format and a monthly period.',
                'props' => [
                    'type' => 'metric',
                    'provider' => 'demo_total_revenue',
                    'label' => 'Total Revenue',
                    'description' => 'Revenue trend for the last 6 months',
                    'value_format' => [
                        'kind' => 'currency',
                        'currency' => 'USD',
                        'maximumFractionDigits' => 2,
                    ],
                    'period' => [
                        'kind' => 'month',
                        'lookback' => 6,
                        'label' => 'this month',
                    ],
                    'trend' => [
                        'precision' => 1,
                    ],
                    'data' => [
                        'value' => 1250,
                        'trend' => [
                            'direction' => 'up',
                            'percent' => 12.5,
                            'comment' => 'Trending up this month',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'new_customers',
                'title' => 'Metric card (number format)',
                'description' => 'Metric widget with a number value format and a monthly period.',
                'props' => [
                    'type' => 'metric',
                    'provider' => 'demo_new_customers',
                    'label' => 'New Customers',
                    'description' => 'Acquisition trend for the last 3 months',
                    'value_format' => [
                        'kind' => 'number',
                        'maximumFractionDigits' => 0,
                    ],
                    'period' => [
                        'kind' => 'month',
                        'lookback' => 3,
                        'label' => 'this period',
                    ],
                    'trend' => [
                        'precision' => 1,
                    ],
                    'data' => [
                        'value' => 1234,
                        'trend' => [
                            'direction' => 'down',
                            'percent' => -20.0,
                            'comment' => 'Down 20% this period',
                        ],
                    ],
                ],
            ],
            [
                'id' => 'status_card',
                'title' => 'Status card',
                'description' => 'Operational status card with state, key value, and last update.',
                'props' => [
                    'type' => 'status',
                    'provider' => 'demo_status_card',
                    'label' => 'API Gateway',
                    'data' => [
                        'status' => 'warning',
                        'value' => '99.91%',
                        'context' => 'Availability over last 24h',
                        'updated_at' => '2m ago',
                        'description' => 'Elevated error rate in EU region',
                    ],
                ],
            ],
            [
                'id' => 'status_card_2',
                'title' => 'Status card',
                'description' => 'Operational status card with state, key value, and last update.',
                'props' => [
                    'type' => 'status',
                    'provider' => 'demo_status_card',
                    'label' => 'Health Check',
                    'data' => [
                        'status' => 'success',
                        'value' => '99.99%',
                        'context' => 'Availability over last 24h',
                        'updated_at' => '2m ago',
                        'description' => 'All services are healthy',
                    ],
                ],
            ],
            [
                'id' => 'traffic_chart',
                'title' => 'Chart widget (stacked area)',
                'description' => 'Time-series chart with provider-resolved points and YAML-driven series.',
                'props' => [
                    'type' => 'chart',
                    'provider' => 'traffic_area_chart',
                    'label' => 'Total visitors',
                    'description' => 'Desktop vs mobile sessions',
                    'chart' => [
                        'x_key' => 'date',
                        'variant' => 'area_stacked',
                        'series' => [
                            ['key' => 'desktop', 'label' => 'Desktop', 'color' => 'success'],
                            ['key' => 'mobile', 'label' => 'Mobile', 'color' => 'info'],
                        ],
                        'time_ranges' => [
                            ['id' => '90d', 'label' => 'Last 3 months'],
                            ['id' => '30d', 'label' => 'Last 30 days'],
                            ['id' => '7d', 'label' => 'Last 7 days'],
                        ],
                    ],
                    'data' => [
                        'points' => [
                            ['date' => '2024-04-01', 'desktop' => 222, 'mobile' => 150],
                            ['date' => '2024-04-15', 'desktop' => 120, 'mobile' => 170],
                            ['date' => '2024-05-01', 'desktop' => 165, 'mobile' => 220],
                            ['date' => '2024-05-15', 'desktop' => 292, 'mobile' => 210],
                            ['date' => '2024-06-01', 'desktop' => 178, 'mobile' => 200],
                            ['date' => '2024-06-15', 'desktop' => 307, 'mobile' => 350],
                            ['date' => '2024-06-30', 'desktop' => 446, 'mobile' => 400],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'traffic_chart_bar',
                'title' => 'Chart widget (grouped bar)',
                'description' => 'Bar chart mode using the same widget data shape.',
                'props' => [
                    'type' => 'chart',
                    'provider' => 'traffic_area_chart',
                    'label' => 'Total visitors',
                    'description' => 'Desktop vs mobile sessions',
                    'chart' => [
                        'mode' => 'bar',
                        'x_key' => 'date',
                        'series' => [
                            ['key' => 'desktop', 'label' => 'Desktop', 'color' => 'success'],
                            ['key' => 'mobile', 'label' => 'Mobile', 'color' => 'info'],
                        ],
                        'time_ranges' => [
                            ['id' => '90d', 'label' => 'Last 3 months'],
                            ['id' => '30d', 'label' => 'Last 30 days'],
                            ['id' => '7d', 'label' => 'Last 7 days'],
                        ],
                    ],
                    'data' => [
                        'points' => [
                            ['date' => '2024-04-01', 'desktop' => 222, 'mobile' => 150],
                            ['date' => '2024-04-15', 'desktop' => 120, 'mobile' => 170],
                            ['date' => '2024-05-01', 'desktop' => 165, 'mobile' => 220],
                            ['date' => '2024-05-15', 'desktop' => 292, 'mobile' => 210],
                            ['date' => '2024-06-01', 'desktop' => 178, 'mobile' => 200],
                            ['date' => '2024-06-15', 'desktop' => 307, 'mobile' => 350],
                            ['date' => '2024-06-30', 'desktop' => 446, 'mobile' => 400],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'traffic_chart_line',
                'title' => 'Chart widget (line)',
                'description' => 'Line chart mode using the same widget data shape.',
                'props' => [
                    'type' => 'chart',
                    'provider' => 'traffic_area_chart',
                    'label' => 'Total visitors',
                    'description' => 'Desktop vs mobile sessions',
                    'chart' => [
                        'mode' => 'line',
                        'x_key' => 'date',
                        'series' => [
                            ['key' => 'desktop', 'label' => 'Desktop', 'color' => 'success'],
                            ['key' => 'mobile', 'label' => 'Mobile', 'color' => 'info'],
                        ],
                        'time_ranges' => [
                            ['id' => '90d', 'label' => 'Last 3 months'],
                            ['id' => '30d', 'label' => 'Last 30 days'],
                            ['id' => '7d', 'label' => 'Last 7 days'],
                        ],
                    ],
                    'data' => [
                        'points' => [
                            ['date' => '2024-04-01', 'desktop' => 222, 'mobile' => 150],
                            ['date' => '2024-04-15', 'desktop' => 120, 'mobile' => 170],
                            ['date' => '2024-05-01', 'desktop' => 165, 'mobile' => 220],
                            ['date' => '2024-05-15', 'desktop' => 292, 'mobile' => 210],
                            ['date' => '2024-06-01', 'desktop' => 178, 'mobile' => 200],
                            ['date' => '2024-06-15', 'desktop' => 307, 'mobile' => 350],
                            ['date' => '2024-06-30', 'desktop' => 446, 'mobile' => 400],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'recent-activity',
                'title' => 'Table widget',
                'description' => 'Table widget can display data from a provider (read-only) or a model (editable).',
                'props' => [
                    'type' => 'table',
                    'columns' => [
                        [
                            'id' => 'id',
                            'label' => 'ID',
                            'type' => 'text',
                        ],
                        [
                            'id' => 'user',
                            'label' => 'User',
                            'type' => 'relation',
                            'relation' => 'user',
                            'relation_name' => 'name',
                            'relation_value' => 'id',
                        ],
                        [
                            'id' => 'activity',
                            'label' => 'Activity',
                            'type' => 'text',
                        ],
                        [
                            'id' => 'created_at',
                            'label' => 'Created at',
                            'type' => 'date',
                            'format' => 'Y-m-d H:i:s',
                        ],
                    ],
                    'data' => [
                        'rows' => [
                            [
                                'id' => 1,
                                'user' => ['id' => 1, 'name' => 'John Doe'],
                                'activity' => 'Logged in',
                                'created_at' => '2024-04-01 12:00:00',
                            ],
                            [
                                'id' => 2,
                                'user' => ['id' => 2, 'name' => 'Jane Doe'],
                                'activity' => 'Logged out',
                                'created_at' => '2024-04-01 12:01:00',
                            ],
                            [
                                'id' => 3,
                                'user' => ['id' => 3, 'name' => 'Jim Doe'],
                                'activity' => 'Created a new post',
                                'created_at' => '2024-04-01 12:02:00',
                            ],
                            [
                                'id' => 4,
                                'user' => ['id' => 2, 'name' => 'Jane Doe'],
                                'activity' => 'Updated a post',
                                'created_at' => '2024-04-01 12:03:00',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Component docs fields catalog for the `/demo` page.
     *
     * @return list<array<string, mixed>>
     */
    private function fieldsCatalog(): array
    {
        return [
            [
                'id' => 'text',
                'title' => 'Text input',
                'description' => 'A text input field.',
                'props' => [
                    'type' => 'text',
                    'label' => 'Text input label',
                    'placeholder' => 'Enter your text here',
                    'helperText' => 'A helper text for the text input field.',
                ],
                'showValue' => false,
                'value' => null,
            ],
            [
                'id' => 'textarea',
                'title' => 'Textarea',
                'description' => 'A textarea input field.',
                'props' => [
                    'type' => 'textarea',
                    'label' => 'Textarea label',
                    'placeholder' => 'Enter your text here',
                    'helperText' => 'A helper text for the textarea input field.',
                    'rows' => 10,
                ],
                'showValue' => false,
                'value' => null,
            ],
            [
                'id' => 'select',
                'title' => 'Select',
                'description' => 'A select input field.',
                'props' => [
                    'type' => 'select',
                    'label' => 'Select label',
                    'placeholder' => 'Select an option',
                    'helperText' => 'A helper text for the select input field.',
                    'options' => [
                        ['value' => 'a', 'label' => 'Option A', 'status' => 'success'],
                        ['value' => 'b', 'label' => 'Option B', 'status' => 'warning'],
                        ['value' => 'c', 'label' => 'Option C', 'status' => 'error'],
                        ['value' => 'd', 'label' => 'Option D', 'status' => 'info'],
                        ['value' => 'e', 'label' => 'Option E', 'status' => 'pending'],
                    ],
                ],
                'showValue' => false,
                'value' => null,
            ],
            [
                'id' => 'combobox',
                'title' => 'Combobox',
                'description' => 'A combobox input field.',
                'props' => [
                    'type' => 'combobox',
                    'label' => 'Combobox label',
                    'placeholder' => 'Select an option',
                    'helperText' => 'A helper text for the combobox input field.',
                    'options' => [
                        ['value' => 'a', 'label' => 'Option A'],
                        ['value' => 'b', 'label' => 'Option B'],
                        ['value' => 'c', 'label' => 'Option C'],
                    ],
                    'multiple' => true,
                ],
                'showValue' => false,
                'value' => null,
            ],
            [
                'id' => 'date-picker',
                'title' => 'Date picker',
                'description' => 'A date picker input field.',
                'props' => [
                    'type' => 'date-picker',
                    'label' => 'Date picker label',
                    'placeholder' => 'Select a date',
                    'helperText' => 'A helper text for the date picker input field.',
                ],
                'showValue' => false,
                'value' => null,
            ],
            [
                'id' => 'date-range-picker',
                'title' => 'Date range picker',
                'description' => 'A date range picker input field.',
                'props' => [
                    'type' => 'date-range-picker',
                    'label' => 'Date range picker label',
                    'placeholder' => 'Select a date range',
                    'helperText' => 'A helper text for the date range picker input field.',
                ],
                'showValue' => false,
                'value' => null,
            ],
            [
                'id' => 'time-picker',
                'title' => 'Time picker',
                'description' => 'A time picker input field.',
                'props' => [
                    'type' => 'time-picker',
                    'label' => 'Time picker',
                    'dateLabel' => 'Date',
                    'timeLabel' => 'Time',
                    'datePlaceholder' => 'Select a date',
                    'timeDefaultValue' => '09:00:00',
                    'helperText' => 'A helper text for the time picker input field.',
                ],
                'showValue' => false,
                'value' => null,
            ],
            [
                'id' => 'checkbox',
                'title' => 'Checkbox',
                'description' => 'A checkbox input field.',
                'props' => [
                    'type' => 'checkbox',
                    'label' => 'Checkbox label',
                    'helperText' => 'A helper text for the checkbox input field.',
                ],
                'showValue' => false,
                'value' => null,
            ],
            [
                'id' => 'switch',
                'title' => 'Switch',
                'description' => 'A switch input field.',
                'props' => [
                    'type' => 'switch',
                    'label' => 'Switch label',
                    'helperText' => 'A helper text for the switch input field.',
                ],
                'showValue' => false,
                'value' => null,
            ],
            [
                'id' => 'rich-text',
                'title' => 'Rich text',
                'description' => 'A rich text input field.',
                'props' => [
                    'type' => 'rich-text',
                    'label' => 'Rich text label',
                    'placeholder' => 'Enter your rich text here',
                    'helperText' => 'A helper text for the rich text input field.',
                    'showFixedToolbar' => true,
                ],
                'showValue' => true,
                'value' => null,
            ],
            [
                'id' => 'block-editor',
                'title' => 'Block editor',
                'description' => 'A block editor input field.',
                'props' => [
                    'type' => 'block-editor',
                    'label' => 'Block editor label',
                    'placeholder' => 'Type / for blocks, or hover the gutter for + and drag…',
                    'helperText' => 'Notion-style: drag blocks, + inserts a paragraph below, right-click for block menu. No top toolbar.',
                    'showFixedToolbar' => true,
                ],
                'showValue' => true,
                'value' => null,
            ],
            [
                'id' => 'data-table',
                'title' => 'Data table',
                'description' => 'A data table input field.',
                'props' => [
                    'type' => 'table',
                    'label' => 'Data table label',
                    'helperText' => 'A helper text for the data table input field.',
                    'bulkActions' => [
                        [
                            'id' => 'delete',
                            'label' => 'Delete',
                            'action' => 'delete',
                            'variant' => 'destructive',
                            'icon' => 'trash',
                        ],
                    ],
                    'reorderable' => true,
                    'actions' => [],
                    'columns' => [
                        [
                            'id' => 'id',
                            'label' => 'ID',
                            'sortable' => true,
                            'searchable' => true,
                        ],
                        [
                            'id' => 'name',
                            'label' => 'Name',
                            'editable' => true,
                            'detailDrawer' => true,
                            'sortable' => true,
                            'searchable' => true,
                        ],
                        [
                            'id' => 'email',
                            'label' => 'Email',
                            'editable' => true,
                            'sortable' => true,
                            'searchable' => true,
                        ],
                        [
                            'id' => 'status',
                            'label' => 'Status',
                            'type' => 'select',
                            'sortable' => true,
                            'options' => [
                                ['value' => 'active', 'label' => 'Active', 'status' => 'success'],
                                ['value' => 'banned', 'label' => 'Banned', 'status' => 'error'],
                                ['value' => 'pending', 'label' => 'Pending', 'status' => 'pending'],
                                ['value' => 'inactive', 'label' => 'Inactive', 'status' => 'warning'],
                            ],
                        ],
                        [
                            'id' => 'created_at',
                            'label' => 'Created at',
                            'type' => 'date',
                            'format' => 'Y-m-d',
                            'timezone' => 'UTC',
                            'sortable' => true,
                            'searchable' => true,
                            'invisible' => true,
                        ],
                        [
                            'id' => 'updated_at',
                            'label' => 'Updated at',
                            'type' => 'date',
                            'format' => 'Y-m-d',
                            'timezone' => 'UTC',
                            'sortable' => true,
                            'searchable' => true,
                            'invisible' => true,
                        ],
                        [
                            'id' => 'actions',
                            'label' => 'Actions',
                            'type' => 'actions',
                            'invisible' => false,
                            'actions' => [
                                'edit' => [
                                    'label' => 'Edit',
                                    'icon' => 'edit',
                                    'action' => 'edit',
                                ],
                                'delete' => [
                                    'label' => 'Delete',
                                    'icon' => 'delete',
                                    'action' => 'delete',
                                ],
                            ],
                        ],
                    ],
                ],
                'showValue' => true,
                'value' => array_map(
                    static fn (): array => [
                        'id' => fake()->uuid(),
                        'name' => fake()->name(),
                        'email' => fake()->email(),
                        'status' => fake()->randomElement(['active', 'inactive', 'pending', 'banned']),
                        'created_at' => fake()->dateTime()->format('Y-m-d H:i:s'),
                        'updated_at' => fake()->dateTime()->format('Y-m-d H:i:s'),
                        'sort_order' => fake()->numberBetween(1, 100),
                    ],
                    range(1, 25),
                ),
            ],
        ];
    }
}
