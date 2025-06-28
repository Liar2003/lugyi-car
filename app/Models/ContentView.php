<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ContentView extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'content_id',
        'device_id',
        'ip_address',
        'user_agent'
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
