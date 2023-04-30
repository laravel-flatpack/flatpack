<?php

test('artisan command that generates a new form and list', function () {
    $this->artisan("make:flatpack Post")
        ->assertSuccessful();

    $this->artisan("make:flatpack Post --force")
        ->assertSuccessful();
});

it('should ask for missing inputs', function () {
    $this->artisan("make:flatpack")
        ->expectsQuestion('What is the model name?', 'Post')
        ->expectsQuestion('Select an icon', 'other')
        ->expectsQuestion('Icon name:', 'collection')
        ->assertSuccessful();

    $this->artisan("make:flatpack")
        ->expectsQuestion('What is the model name?', 'array')
        ->expectsOutputToContain('The name "Array" is reserved by PHP.');

    $this->artisan("make:flatpack")
        ->expectsQuestion('What is the model name?', 'foo-bar')
        ->expectsQuestion('Select an icon', 'none')
        ->expectsOutputToContain('The model "Flatpack\Tests\Models\FooBar" does not exist.');
});
