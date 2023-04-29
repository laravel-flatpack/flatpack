<?php

use Flatpack\Traits\WithFormFields;
use Flatpack\Traits\WithRelationships;

beforeEach(function () {
    $this->trait = new class () {
        use WithRelationships, WithFormFields {
            bindModelToFields as public;
            bindFieldsToModel as public;
        }

        public $formFields = [];
        public $fields = [];
        public $entry;

        public function __construct()
        {
            $this->fieldsBinding = 'fields';

            $this->entry = new \Flatpack\Tests\Models\Post();

            $this->formFields = [
                'title' => [
                    'label' => 'Title',
                    'type' => 'text',
                    'placeholder' => 'Enter a title',
                    'rules' => 'required|string',
                ],
                'body' => [
                    'label' => 'Body',
                    'type' => 'editor',
                    'rules' => 'required|string',
                ],
                'category' => [
                    'label' => 'Category',
                    'type' => 'relation',
                    'relation' => [
                        'name' => 'category',
                        'display' => 'name',
                    ],
                ],
                'tags' => [
                    'label' => 'Tags',
                    'type' => 'relation',
                    'relation' => [
                        'name' => 'tags',
                        'display' => 'name',
                    ],
                ],
                'created_at' => [
                    'label' => 'Created at',
                    'disabled' => true,
                    'type' => 'datetime-picker',
                ],
                'updated_at' => [
                    'label' => 'Updated at',
                    'disabled' => true,
                    'type' => 'datetime-picker',
                ],
            ];
        }
    };
});

it('binds fields attribute to the active record entry values', function () {
    $this->trait->fields = [
        'title' => 'Lorem ipsum',
        'body' => 'Lorem ipsum dolor sit amet',
        'tags' => null,
        'created_at' => '2022-01-01 00:00:00',
        'updated_at' => '2022-01-01 00:00:00',
    ];

    $this->trait->bindFieldsToModel();

    $this->expect($this->trait->entry->title)->toBe('Lorem ipsum');
});

it('binds the active record entry values to the fields attribute', function () {
    $this->trait->entry->title = 'Something else';
    $this->trait->entry->body = 'Lorem ipsum dolor sit amet';
    $this->trait->entry->category = new \Flatpack\Tests\Models\Category([
        'id' => 1,
        'name' => 'Category 1',
    ]);
    $this->trait->entry->tags = \Illuminate\Database\Eloquent\Collection::make([
        new \Flatpack\Tests\Models\Tag([
            'id' => 1,
            'name' => 'Tag 1',
        ]),
        new \Flatpack\Tests\Models\Tag([
            'id' => 2,
            'name' => 'Tag 2',
        ]),
    ]);
    $this->trait->entry->created_at = \Carbon\Carbon::parse('2022-01-01 00:00:00');
    $this->trait->entry->updated_at = \Carbon\Carbon::parse('2022-01-12 00:00:00');

    $this->trait->bindModelToFields();

    $this->expect($this->trait->fields['title'])->toBe($this->trait->entry->title);
    $this->expect($this->trait->fields['body'])->toBe($this->trait->entry->body);
    $this->expect($this->trait->fields['category'])->toBe(1);
    $this->expect($this->trait->fields['tags'])->toBe([1, 2]);
    $this->expect($this->trait->fields['created_at'])->toBeInstanceOf(\Carbon\Carbon::class);
    $this->expect($this->trait->fields['created_at']->format('Y-m-d H:i:s'))
         ->toBe('2022-01-01 00:00:00');
    $this->expect($this->trait->fields['updated_at'])->toBeInstanceOf(\Carbon\Carbon::class);
    $this->expect($this->trait->fields['updated_at']->format('Y-m-d H:i:s'))
         ->toBe('2022-01-12 00:00:00');
});
