<?php

use Faustoq\Flatpack\Flatpack;

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
