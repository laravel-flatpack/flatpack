<?php

declare(strict_types=1);

namespace Flatpack\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class PostMeta extends Model
{
    use HasFactory;

    protected $table = 'post_metas';

    protected $fillable = [
        'post_id',
        'subtitle',
    ];
}
