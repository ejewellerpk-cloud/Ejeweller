<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TopBarResource extends JsonResource
{
    public array $info;

    public function __construct($info)
    {
        parent::__construct($info);
        $this->info = $info;
    }

    public function toArray($request): array
    {
        return [
            'top_bar_status'     => $this->info['top_bar_status'] ?? 'inactive',
            'top_bar_text'       => $this->info['top_bar_text'] ?? '',
            'top_bar_link'       => $this->info['top_bar_link'] ?? '',
            'top_bar_bg_color'   => $this->info['top_bar_bg_color'] ?? '#ff5c00',
            'top_bar_text_color' => $this->info['top_bar_text_color'] ?? '#ffffff',
        ];
    }
}
