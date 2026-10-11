<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = ['purchase_id', 'item_id', 'quantity', 'purchase_price'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}