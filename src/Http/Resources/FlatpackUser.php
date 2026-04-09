<?php

declare(strict_types=1);

namespace Flatpack\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read string|null $name
 * @property-read string|null $email
 * @property-read string|null $avatar
 */
final class FlatpackUser extends JsonResource
{
    public static $wrap = null;

    #[Override]
    public function toArray($request): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
        ];
    }
}
