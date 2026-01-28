<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaravelKnowledge extends Model
{
    protected $fillable = ['url', 'source', 'title', 'content', 'vector'];
    protected $casts = [
        'vector' => 'array',
    ];
}
