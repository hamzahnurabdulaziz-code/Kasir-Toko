<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'product_id',
        'type',
        'qty_delta',
        'before_qty',
        'after_qty',
        'ref_type',
        'ref_id',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'qty_delta' => 'decimal:3',
            'before_qty' => 'decimal:3',
            'after_qty' => 'decimal:3',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
