<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Content extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'title',
        'profileImg',
        'coverImg',
        'duration',
        'links',
        'content',
        'tags',
        'category',
        'casts',
        'files',
        'isvip'
    ];

    protected $casts = [
        'links' => 'array',
        'tags' => 'array',
        'casts' => 'array',
        'files' => 'array',
        'isvip' => 'boolean'
    ];
    // Add this relationship
    public function views()
    {
        return $this->hasMany(ContentView::class);
    }

    // Add this method
    public function getViewCountAttribute()
    {
        return $this->views()->count();
    }
}
