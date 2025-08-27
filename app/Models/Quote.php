<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'company_id',
        'medical_institution_id',
        'quote_number',
        'issue_date',
        'expiry_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'discount_rate',
        'discount_amount',
        'total_amount',
        'technical_terms',
        'payment_terms',
        'status',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function medicalInstitution()
    {
        return $this->belongsTo(MedicalInstitution::class);
    }

    public function items()
    {
        return $this->hasMany(QuoteItem::class);
    }
}
