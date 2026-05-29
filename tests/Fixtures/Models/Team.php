<?php

namespace App\Models;

use Afterburner\Subscriptions\Concerns\HasSubscriptions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasSubscriptions;

    protected $fillable = [
        'name',
        'user_id',
        'timezone',
        'trial_ends_at',
        'subscription_plan_id',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
        ];
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
