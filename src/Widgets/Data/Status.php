<?php

declare(strict_types=1);

namespace Flatpack\Widgets\Data;

enum Status: string
{
    case DEFAULT = 'default';
    case ERROR = 'error';
    case INFO = 'info';
    case SUCCESS = 'success';
    case WARNING = 'warning';
}
