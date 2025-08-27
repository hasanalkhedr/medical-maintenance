<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'title',
        'company_id',
        'medical_institution_id',
        'contract_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_rate',
        'discount_amount',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'status',
    ];
    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
    ];
    protected static function booted()
    {
        static::creating(function ($invoice) {
            // Set initial values
            $invoice->paid_amount = $invoice->paid_amount ?? 0;
            $invoice->remaining_amount = $invoice->total_amount - $invoice->paid_amount;
        });

        static::updating(function ($invoice) {
            // Recalculate remaining amount whenever paid_amount changes
            $invoice->remaining_amount = $invoice->total_amount - $invoice->paid_amount;

            // Update status based on payment
            $invoice->updateStatus(false);
        });

        static::saved(function ($invoice) {
            // Update status after saving
            $invoice->updateStatus(false);
        });
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function medicalInstitution()
    {
        return $this->belongsTo(MedicalInstitution::class);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
    public function updateStatus(bool $save = true): void
    {
        $oldStatus = $this->status;

        if ($this->paid_amount >= $this->total_amount) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'pending';
        }

        // Only save if status changed and save is requested
        if ($save && $this->isDirty('status')) {
            $this->saveQuietly();
        }
    }
    /**
     * Add a payment and update invoice totals
     */
    public function addPayment(Payment $payment): Payment
    {
        // Update invoice amounts directly
        $this->paid_amount += $payment->amount;
        $this->remaining_amount = max(0, $this->total_amount - $this->paid_amount);

        // Update status without saving (we'll save after)
        $this->updateStatus(false);

        // Save the invoice with updated amounts and status
        $this->save();
        return $payment;
    }
    /**
     * Get paid amount percentage
     */
    public function getPaidPercentageAttribute(): float
    {
        if ($this->total_amount == 0) {
            return 0;
        }

        return ($this->paid_amount / $this->total_amount) * 100;
    }

    /**
     * Check if invoice is fully paid
     */
    public function isFullyPaid(): bool
    {
        return $this->paid_amount >= $this->total_amount;
    }

    /**
     * Check if invoice is partially paid
     */
    public function isPartiallyPaid(): bool
    {
        return $this->paid_amount > 0 && $this->paid_amount < $this->total_amount;
    }

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date->isPast() && !$this->isFullyPaid();
    }

    /**
     * Scope for overdue invoices
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('status', '!=', 'paid');
    }

    public function loadPayments()
{
    return $this->load(['payments' => function($query) {
        $query->orderBy('payment_date', 'desc');
    }]);
}
}
