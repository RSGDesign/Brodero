<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','coupon_code','discount_cents'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class,'coupon_code','code');
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    public function subtotalCents(): int
    {
        return $this->items->sum(fn($i) => $i->quantity * $i->price_cents_snapshot);
    }

    public function totalCents(): int
    {
        return max(0, $this->subtotalCents() - $this->discount_cents);
    }
}
