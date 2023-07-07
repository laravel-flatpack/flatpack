<?php

use Flatpack\Http\Livewire\Dashboard;
use Livewire\Livewire;

it('displays dashboard', function () {
    Livewire::test(Dashboard::class)
        ->assertSee('Flatpack');
});
