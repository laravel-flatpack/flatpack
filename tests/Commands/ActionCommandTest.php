<?php

test('artisan command that generates an action class', function () {
    $this->artisan('flatpack:action custom-action')->assertSuccessful();
    $this->artisan('flatpack:action custom-action --force=true')->assertSuccessful();
});
