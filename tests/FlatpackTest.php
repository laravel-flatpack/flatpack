<?php

use Flatpack\Facades\Flatpack;

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
    expect($result)->toBe("Flatpack\Tests\Models\User");
});

it('loads and returns the composition', function () {
    $flatpack = Flatpack::loadComposition();
    expect($flatpack)->toBeInstanceOf(\Flatpack\Flatpack::class);

    $composition = $flatpack->getComposition();
    expect($composition)->toBeArray();
});

it('gets a template form composition by entity', function () {
    $flatpack = Flatpack::loadComposition();
    expect($flatpack)->toBeInstanceOf(\Flatpack\Flatpack::class);

    $composition = $flatpack->getTemplateComposition('posts', 'form.yaml');

    $snapshot = require_once(__DIR__ . '/__snapshots__/posts-form.php');

    expect($composition)->toBeArray();
    expect($composition)->toMatchArray($snapshot);
});

it('gets a template list composition by entity', function () {
    $flatpack = Flatpack::loadComposition();
    expect($flatpack)->toBeInstanceOf(\Flatpack\Flatpack::class);

    $composition = $flatpack->getTemplateComposition('posts', 'list.yaml');

    $snapshot = require_once(__DIR__ . '/__snapshots__/posts-list.php');

    expect($composition)->toBeArray();
    expect($composition)->toMatchArray($snapshot);
});

it('throws an exception when entity is not found', function () {
    $flatpack = Flatpack::loadComposition();
    expect($flatpack)->toBeInstanceOf(\Flatpack\Flatpack::class);

    $this->expectException(\Flatpack\Exceptions\EntityNotFoundException::class);
    $flatpack->getTemplateComposition('not-found-entity', 'form.yaml');
});

it('throws an exception when template is not found', function () {
    $flatpack = Flatpack::loadComposition();
    expect($flatpack)->toBeInstanceOf(\Flatpack\Flatpack::class);

    $this->expectException(\Flatpack\Exceptions\TemplateNotFoundException::class);
    $flatpack->getTemplateComposition('posts', 'file-not-found.yaml');
});
