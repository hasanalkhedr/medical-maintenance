<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::creating(function ($item) {
            $item->amount = ($item->quantity * $item->unit_price);
        });

        static::updating(function ($item) {
            $item->amount = ($item->quantity * $item->unit_price);
        });
    }
    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'amount',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
