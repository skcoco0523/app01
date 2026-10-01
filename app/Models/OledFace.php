<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OledFace extends Model
{
    use HasFactory;

    protected $table = 'oled_faces';

    protected $fillable = ['title', 'event_type', 'interval_ms', 'editor_json', 'compiled_device_json', 'point_cost',];

    protected $casts = [
        'editor_json' => 'array',
        'compiled_device_json' => 'array',
    ];
}
