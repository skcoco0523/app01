<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvResearch extends Model
{
    use HasFactory;

    protected $table = 'adv_researches';

    public $timestamps = false;

    protected $fillable = ['user_id', 'adv_category_id', 'display_seconds', 'score', 'type', ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(AdvCategory::class, 'adv_category_id');
    }
}
