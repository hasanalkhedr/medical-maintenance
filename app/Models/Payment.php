<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
    ];

    protected static function booted()
    {
        static::created(function ($payment) {
            // Update invoice paid amount when payment is created
            $payment->invoice->addPayment($payment);
        });
        static::updated(function ($payment) {
            // Update invoice paid amount when payment is created
            $payment->invoice->addPayment($payment);
        });

        static::deleted(function ($payment) {
            // Subtract payment amount from invoice when deleted
            $payment->invoice?->decrement('paid_amount', $payment->amount);
            $payment->invoice?->increment('remaining_amount', $payment->amount);
            $payment->invoice?->updateStatus();
        });
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function check()
    {
        return $this->hasOne(Check::class);
    }

    public function bank_transfer()
    {
        return $this->hasOne(BankTransfer::class);
    }
    public static function getPaymentMethods(): array
    {
        return [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'check' => 'Check',
            // 'credit_card' => 'Credit Card',
            // 'debit_card' => 'Debit Card',
            // 'online' => 'Online Payment',
        ];
    }
}
