<?php

namespace Flatpack\Contracts;

interface FlatpackUser
{
    public function isFlatpackAdmin();

    public function getFlatpackUserAvatar();
}
