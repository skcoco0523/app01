<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvApiCache extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'keyword',
        'response_json',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(AdvCategory::class, 'category_id');
    }
}
