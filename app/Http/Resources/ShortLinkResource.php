<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShortLinkResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=> $this->id,
            'original_url' => $this->original_url,
            'short_code' => $this->code,
            'short_url' => $this->getShortUrlAttribute(),
            'visits_count' => $this->clicks,
            'expires_at' => $this->expires_at,
            'is_expired' => $this->isExpired(),
            'is_valid' => $this->isValid(),
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
