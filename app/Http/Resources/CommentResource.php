<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'comment' => $this->comment,
            'commenter' => $this->commenter ? [
                'id' => $this->commenter->id,
                'name' => $this->commenter->name,
                'type' => class_basename($this->commenter_type),
            ] : null,
            'attachments' => $this->attachments->map(function ($att) {
                return [
                    'id' => $att->id,
                    'file_name' => $att->file_name,
                    'file_type' => $att->file_type,
                    'file_size' => $att->file_size,
                    'file_url' => url('/storage/' . $att->file_path),
                ];
            }),
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : null,
        ];
    }
}
