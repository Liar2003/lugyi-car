<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Suggestion extends Model
{
    //
    use HasFactory;
    protected $fillable= [
        'contentId',
        'imgUrl',
        'link',
        'is_ads'
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

}
