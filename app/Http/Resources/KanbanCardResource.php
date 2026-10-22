<?php

namespace App\Http\Resources;

use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Application
 */
class KanbanCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'job_stage_id' => $this->job_stage_id,
            'status' => $this->status,
            'candidate' => [
                'id' => $this->candidate->id,
                'name' => $this->candidate->name,
                'email' => $this->candidate->email,
            ],
            'created_at' => $this->created_at,
            'stage_entered_at' => $this->stage_entered_at,
        ];
    }
}
