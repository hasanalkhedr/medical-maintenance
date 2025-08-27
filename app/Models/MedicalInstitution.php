<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicalInstitution extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'address',
        'phone',
        'email',
        'contact_person',
        'contact_person_phone',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }
    public function payments()
{
    return $this->hasManyThrough(Payment::class, Invoice::class);
}
}
