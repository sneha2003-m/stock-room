<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = ['sale_id', 'item_id', 'quantity', 'selling_price'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}