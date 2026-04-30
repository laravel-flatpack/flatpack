<?php

declare(strict_types=1);

namespace Flatpack\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

final class ExplodingRelationModel extends Model
{
    public $timestamps = false;

    protected $table = 'posts';

    public function lines(): HasMany
    {
        throw new RuntimeException('Relation resolution exploded.');
    }
}
