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
        return FlatpackResponse::inertia('demo', [
            'query' => $request->query(),
            'catalog' => $this->catalog(),
        ], $request->boolean('json'));
    }

    /**
     * Component docs catalog for the `/demo` page.
     *
     * @return list<array<string, mixed>>
     */
    private function catalog(): array
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
