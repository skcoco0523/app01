<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OledSpriteSheet extends Model
{
    use HasFactory;

    protected $table = 'oled_sprite_sheets';

    protected $fillable = ['name','file_path','part_data',];
    protected $casts = [
        'part_data' => 'array',
    ];

}
