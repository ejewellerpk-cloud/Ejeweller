<?php

namespace App\Http\Resources;

use App\Libraries\AppLibrary;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            "id"                   => $this->id,
            "user_id"              => $this->user_id,
            "user_name"            => $this->display_name,
            "product_id"           => $this->product_id,
            "product_name"         => $this->product?->name,
            "product_thumb"        => $this->product?->thumb,
            "star"                 => $this->star,
            "review"               => $this->review,
            "images"               => $this->images,
            "status"               => (int) ($this->status ?? \App\Enums\ReviewStatus::APPROVED),
            "is_verified_purchase" => (int) ($this->is_verified_purchase ?? \App\Enums\Ask::NO),
            "date"                 => $this->created_at ? AppLibrary::date($this->created_at, 'd M Y') : '',
        ];
    }
}
