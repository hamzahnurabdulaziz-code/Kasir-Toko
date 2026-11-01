<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'setting_key',
        'setting_value',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
