<?php

test('artisan command that generates an action class', function () {
    $this->artisan('flatpack:action custom-action')
        ->expectsOutput("\n 📦 Flatpack \n")
        ->assertSuccessful();

    $this->artisan('flatpack:action custom-action --force=true')
        ->expectsOutput("\n 📦 Flatpack \n")
        ->assertSuccessful();
});
