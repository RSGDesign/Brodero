<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $table = 'media';

    protected $fillable = [
        'path',
        'original_name',
        'mime_type',
        'size'
    ];

    protected $casts = [
        'size' => 'integer'
    ];
}
