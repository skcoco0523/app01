<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvCategory extends Model
{
    use HasFactory;

    protected $table = 'adv_categories';

    protected $fillable = [
        'name',
        'search_keywords',
        'enable_flag',
    ];
}
