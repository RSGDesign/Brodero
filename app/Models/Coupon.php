<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code','type','value','expires_at','active','max_uses','uses_count','min_order_value'
    ];

    protected $casts = [
        'expires_at' => 'date',
        'active' => 'boolean',
        'value' => 'decimal:2',
        'min_order_value' => 'decimal:2'
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function isUsable(float $orderValue): bool
    {
        if(!$this->active) return false;
        if($this->expires_at && $this->expires_at->isPast()) return false;
        if($this->max_uses > 0 && $this->uses_count >= $this->max_uses) return false;
        if($orderValue < (float)$this->min_order_value) return false;
        return true;
    }
}
