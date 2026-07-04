<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTipGenerationRequest extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'status',
        'prompt',
        'tag_names',
        'requested_count',
        'created_count',
        'failed_count',
        'error_message',
        'started_at',
        'completed_at',
        'dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'tag_names' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'dismissed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
