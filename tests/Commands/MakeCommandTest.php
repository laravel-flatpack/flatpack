<?php

test('artisan command that generates a new form and list', function () {
    $this->artisan('make:flatpack Post')
        ->expectsOutput("\n 📦 Flatpack \n")
        ->expectsOutput("\nDone!\n")
        ->assertExitCode(0)
        ->assertSuccessful();
});
