<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'role' => $this->role,
            'capabilities' => $this->capabilities ?? [],
            'model_preference' => $this->profile?->model_preference,
            'temperature_policy' => $this->profile?->temperature_policy,
            'active' => $this->status?->isOperational() ?? false,
            'status' => $this->status?->value,
            'profile_id' => $this->profile?->id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
