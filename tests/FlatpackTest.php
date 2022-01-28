<?php

use Faustoq\Flatpack\Facades\Flatpack;

it('converts a string to entity name', function () {
    $input = "User";
    $result = Flatpack::entityName($input);
    expect($result)->toBe("users");
});

it('converts a string to model name', function () {
    $input = "users";
    $result = Flatpack::modelName($input);
    expect($result)->toBe("User");
});

it('converts a string to model class name', function () {
    $input = "users";
    $result = Flatpack::guessModelClass($input);
    expect($result)->toBe("User");
});

it('loads and returns the composition', function () {
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

it('throws an exception when entity is not found', function () {
    $flatpack = Flatpack::loadComposition();
    expect($flatpack)->toBeInstanceOf(Faustoq\Flatpack\Flatpack::class);

    $this->expectException(Faustoq\Flatpack\Exceptions\EntityNotFoundException::class);
    $flatpack->getTemplateComposition('not-found-entity', 'form.yaml');
});

it('throws an exception when template is not found', function () {
    $flatpack = Flatpack::loadComposition();
    expect($flatpack)->toBeInstanceOf(Faustoq\Flatpack\Flatpack::class);

    $this->expectException(Faustoq\Flatpack\Exceptions\TemplateNotFoundException::class);
    $flatpack->getTemplateComposition('posts', 'file-not-found.yaml');
});
