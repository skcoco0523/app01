<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvUserScore extends Model
{
    use HasFactory;

    protected $table = 'adv_user_scores';

    protected $fillable = [
        'user_id',
        'adv_category_id',
        'score',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(AdvCategory::class, 'adv_category_id');
    }
}
