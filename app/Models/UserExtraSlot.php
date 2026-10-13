<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserExtraSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'life_theater_limit',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}