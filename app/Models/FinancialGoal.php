<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialGoal extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'financial_goals';

    protected $fillable = [
        'user_id',
        'name',
        'target_amount',
        'current_amount',
        'deadline',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'id'             => 'string',
            'user_id'        => 'string',
            'target_amount'  => 'integer',
            'current_amount' => 'integer',
            'deadline'       => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getProgressAttribute(): float
    {
        if ($this->target_amount <= 0) {
            return 0;
        }

        return round(
            ($this->current_amount / $this->target_amount) * 100,
            2
        );
    }

    public function getRemainingAttribute(): int
    {
        return max(0, $this->target_amount - $this->current_amount);
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->current_amount >= $this->target_amount;
    }
}
