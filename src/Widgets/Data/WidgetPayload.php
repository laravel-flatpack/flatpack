<?php

declare(strict_types=1);

namespace Flatpack\Widgets\Data;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

interface WidgetPayload extends Arrayable, Jsonable {}
