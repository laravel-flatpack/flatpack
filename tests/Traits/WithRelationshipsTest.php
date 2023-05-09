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
                'author' => [
                    'label' => 'Author',
                    'type' => 'relation',
                    'relation' => [
                        'name' => 'author',
                        'display' => 'name',
                    ],
                ],
                'categories' => [
                    'label' => 'Categories',
                    'type' => 'relation',
                    'relation' => [
                        'name' => 'categories',
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
        $this->trait->relation('categories'),
        $this->trait->entry->categories()
    );
});

it('returns null for non existing relationship', function () {
    $this->expect($this->trait->createRelationship('foo', 'bar', 'baz'))
        ->toBe(null);

    $this->assertEquals(
        $this->trait->relation(null),
        null
    );
});

it('checks if the field is a valid relationship', function () {
    $this->expect($this->trait->isRelationship('categories'))
        ->toBe(true);

    $this->expect($this->trait->isRelationship('title'))
        ->toBe(false);

    $this->trait->entry = null;
    $this->expect($this->trait->isRelationship('categories'))
        ->toBe(false);
});

it('sync relationship models', function () {
    $this->trait->entry->title = 'Post title';
    $this->trait->entry->save();
    $author = \Flatpack\Tests\Models\User::factory()->create([
        'name' => 'Test',
        'email' => 'test@test.com',
    ]);
    \Flatpack\Tests\Models\Category::factory()->create([
        'name' => 'Lorem Ipsum',
        'slug' => 'lorem ipsum',
    ]);
    \Flatpack\Tests\Models\Category::factory()->create([
        'name' => 'Lorem Ipsum 2',
        'slug' => 'lorem ipsum 2',
    ]);
    $this->trait->entry->categories()->sync([1, 2]);
    $this->trait->entry->author()->associate($author);
    $this->trait->entry->save();

    $category1 = \Flatpack\Tests\Models\Category::whereId(1)->first();
    $category1->slug = 'lorem-ipsum';
    $category1->save();

    $category2 = \Flatpack\Tests\Models\Category::whereId(2)->first();
    $category2->slug = 'lorem-ipsum-2';
    $category2->save();

    $author->name = 'Foo Bar';
    $author->save();

    $this->trait->fields = [
        'title' => $this->trait->entry->name,
        'categories' => $this->trait->entry->categories->pluck('id')->toArray(),
        'author' => $this->trait->entry->author->id,
    ];

    $this->trait->syncRelationship('categories');
    $this->trait->syncRelationship('author');

    $this->expect($this->trait->entry->categories()->pluck('slug')->toArray())
        ->toBe([
            'lorem-ipsum',
            'lorem-ipsum-2',
        ]);

    $this->expect(optional($this->trait->entry->author)->name)
    ->toBe('Foo Bar');
});

it('creates a new related model instance', function () {
    $this->expect($this->trait->createRelationship('tags', 'name', 'My Tag'))
        ->toBe(1);
});
