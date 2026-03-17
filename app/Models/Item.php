<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'status',
        'risk_score',
        'flags',
        'suggested_action',
        'reviewer_note',
        'reviewed_at',
    ];

    protected $casts = [
        'flags' => 'array',
        'reviewed_at' => 'datetime',
        'risk_score' => 'integer',
    ];
}
