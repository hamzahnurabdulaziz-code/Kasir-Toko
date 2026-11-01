<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'store_id',
        'category_id',
        'unit_id',
        'sku',
        'barcode',
        'name',
        'cost_price',
        'sale_price',
        'min_stock',
        'is_active',
        'track_stock',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'min_stock' => 'decimal:3',
            'is_active' => 'boolean',
            'track_stock' => 'boolean',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockBalance(): HasOne
    {
        return $this->hasOne(StockBalance::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function getOnHandStockAttribute(): float
    {
        return (float) ($this->stockBalance?->on_hand ?? 0);
    }

    public function getReservedStockAttribute(): float
    {
        return (float) ($this->stockBalance?->reserved ?? 0);
    }

    public function getAvailableStockAttribute(): float
    {
        if (!$this->track_stock) {
            return 999999;
        }

        return max(0.0, $this->on_hand_stock - $this->reserved_stock);
    }

    public function isLowStock(): bool
    {
        if (!$this->track_stock) {
            return false;
        }

        return $this->available_stock <= (float) $this->min_stock;
    }
}
