<?php

test('artisan command that generates an action class', function () {
    $this->artisan('flatpack:widget TotalPosts')
        ->expectsOutput("\n 📦 Flatpack \n")
        ->assertSuccessful();

    $this->artisan('flatpack:action TotalPosts --force=true')
        ->expectsOutput("\n 📦 Flatpack \n")
        ->assertSuccessful();
});
