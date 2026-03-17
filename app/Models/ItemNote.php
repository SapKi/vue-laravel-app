<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemNote extends Model
{
    protected $fillable = ['item_id', 'body'];

    protected $casts = ['created_at' => 'datetime'];
}
