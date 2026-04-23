<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'provider',
    'provider_user_id',
    'email',
    'nickname',
    'raw_profile',
    'access_token',
    'refresh_token',
    'token_expires_at',
])]
class SocialAccount extends Model
{
    protected function casts(): array
    {
        return [
            'raw_profile' => 'array',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
