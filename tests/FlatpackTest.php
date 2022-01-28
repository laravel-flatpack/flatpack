<?php

use Faustoq\Flatpack\Facades\Flatpack;

it('Converts a string to entity name', function () {
    $input = "User";
    $result = Flatpack::entityName($input);
    expect($result)->toBe("users");
});

it('Converts a string to model name', function () {
    $input = "users";
    $result = Flatpack::modelName($input);
    expect($result)->toBe("User");
});

it('Converts a string to model class name', function () {
    $input = "users";
    $result = Flatpack::guessModelClass($input);
    expect($result)->toBe("User");
});

it('Loads and returns the composition', function () {
    $flatpack = Flatpack::loadComposition();
    expect($flatpack)->toBeInstanceOf(Faustoq\Flatpack\Flatpack::class);

    $composition = $flatpack->getComposition();
    expect($composition)->toBeArray();
});

it('gets a template composition by entity', function () {
    $flatpack = Flatpack::loadComposition();
    expect($flatpack)->toBeInstanceOf(Faustoq\Flatpack\Flatpack::class);

    $composition = $flatpack->getTemplateComposition('posts', 'form.yaml');

    $snapshot = require_once(__DIR__ . '/__snapshots__/posts.php');

    expect($composition)->toBeArray();
    expect($composition)->toMatchArray($snapshot);
});
