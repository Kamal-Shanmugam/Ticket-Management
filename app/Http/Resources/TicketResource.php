<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'priority' => $this->priority ? [
                'id' => $this->priority->id,
                'name' => $this->priority->name,
                'slug' => $this->priority->slug,
                'resolution_hours' => $this->priority->resolution_hours,
            ] : null,
            'status' => $this->status ? [
                'id' => $this->status->id,
                'name' => $this->status->name,
                'slug' => $this->status->slug,
            ] : null,
            'assigned_to' => new EmployeeResource($this->whenLoaded('assignedTo')),
            'estimated_resolution_at' => $this->estimated_resolution_at ? $this->estimated_resolution_at->format('Y-m-d H:i:s') : null,
            'actual_resolution_at' => $this->actual_resolution_at ? $this->actual_resolution_at->format('Y-m-d H:i:s') : null,
            'sla_warning_notified' => $this->sla_warning_notified,
            'sla_breached_notified' => $this->sla_breached_notified,
            'is_sla_breached' => $this->isSlaBreached(),
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'attachments' => $this->attachments->map(function ($att) {
                return [
                    'id' => $att->id,
                    'file_name' => $att->file_name,
                    'file_type' => $att->file_type,
                    'file_size' => $att->file_size,
                    'file_url' => url('/storage/' . $att->file_path),
                ];
            }),
            'logs' => $this->logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'details' => $log->details,
                    'performed_by' => $log->performedBy ? [
                        'id' => $log->performedBy->id,
                        'name' => $log->performedBy->name,
                        'type' => class_basename($log->performed_by_type),
                    ] : [
                        'name' => 'System',
                        'type' => 'System',
                    ],
                    'created_at' => $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : null,
                ];
            }),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
