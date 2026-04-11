<?php

declare(strict_types=1);

namespace Flatpack\Http\Controllers;

use Flatpack\Http\Responses\FlatpackResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

final class DemoController
{
    public function index(Request $request): Response|JsonResponse
    {
        return FlatpackResponse::inertia('demo/components', [
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
                ],
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
                        ['value' => 'a', 'label' => 'Option A'],
                        ['value' => 'b', 'label' => 'Option B'],
                        ['value' => 'c', 'label' => 'Option C'],
                    ],
                ],
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
                'output' => [
                    'show' => true,
                    'label' => 'Exported JSON (value)',
                ],
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
                'output' => [
                    'show' => true,
                    'label' => 'Exported JSON (value)',
                ],
                'value' => null,
            ],
        ];
    }
}
