<?php

use Flatpack\Traits\WithRelationships;

beforeEach(function () {
    $this->trait = new class () {
        use WithRelationships {
            relation as public;
            createRelationship as public;
            syncRelationship as public;
            isRelationship as public;
        }

        public $formFields = [];
        public $fields = [];
        public $entry;

        public function __construct()
        {
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

it('returns the field relationship', function () {
    $this->assertEquals(
        $this->trait->relation('category'),
        $this->trait->entry->category()
    );
});

it('checks if the field is a valid relationship', function () {
    $this->expect($this->trait->isRelationship('category'))
        ->toBe(true);

    $this->expect($this->trait->isRelationship('title'))
        ->toBe(false);

    $this->trait->entry = null;
    $this->expect($this->trait->isRelationship('category'))
        ->toBe(false);
});
