<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_item_id',
        'stock_balance_id',
        'quantity',
        'status', // RESERVED, COMMITTED, RELEASED
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function stockBalance(): BelongsTo
    {
        return $this->belongsTo(StockBalance::class);
    }
}

