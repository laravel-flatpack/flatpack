<?php

declare(strict_types=1);

namespace Flatpack\Tests\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Minimal model for composition generator date vs datetime mapping tests (no migrations).
 */
final class GuineaPigModel extends Model
{
    protected $table = 'guinea_pigs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'happens_at',
        'on_calendar_date',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'happens_at' => 'datetime',
        'on_calendar_date' => 'date',
    ];
}
