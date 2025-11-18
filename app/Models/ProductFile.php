<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id','filename','original_name','filesize'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
