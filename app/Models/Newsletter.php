<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Newsletter extends Model
{
    use HasFactory;

    protected $table = 'newsletter';

    protected $fillable = [
        'email','subscribed_at'
    ];

    protected $casts = [
        'subscribed_at' => 'datetime'
    ];
    
    protected $appends = ['created_at'];
    
    public function getCreatedAtAttribute()
    {
        return $this->subscribed_at;
    }
}
