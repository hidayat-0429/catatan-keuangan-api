<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'month',
        'year',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'id'          => 'string',
            'user_id'     => 'string',
            'category_id' => 'string',
            'amount'      => 'integer',
            'month'       => 'integer',
            'year'        => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Hitung total pengeluaran bulan ini untuk kategori ini.
     */
    public function getSpentAttribute(): int
    {
        return Transaction::query()
            ->where('user_id', $this->user_id)
            ->where('category_id', $this->category_id)
            ->where('type', 'pengeluaran')
            ->whereYear('transaction_date', $this->year)
            ->whereMonth('transaction_date', $this->month)
            ->whereNull('deleted_at')
            ->sum('amount');
    }
}
