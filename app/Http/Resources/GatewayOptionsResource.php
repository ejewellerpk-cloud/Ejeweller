<?php

namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class GatewayOptionsResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request) : array
    {

        return [
            'id' => $this->id,
            'option' => $this->option,
            'value' => $this->value === null ? '' : (string) $this->value,
            'type' => (int) $this->type,
            'activities' => $this->decodedActivities(),
        ];
    }

    protected function decodedActivities()
    {
        $activities = $this->activities;
        if (is_array($activities)) {
            return $activities;
        }
        if (!is_string($activities) || $activities === '') {
            return (object) [];
        }

        $decoded = json_decode($activities, true);
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        return is_array($decoded) ? $decoded : (object) [];
    }
}
