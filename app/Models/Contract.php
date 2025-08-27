<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'medical_institution_id',
        'contract_number',
        'start_date',
        'end_date',
        'total_amount',
        'payment_schedule',
        'terms',
        'status',
        'contract_file',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function medicalInstitution()
    {
        return $this->belongsTo(MedicalInstitution::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Generate invoices based on payment schedule
     */
    public function generateInvoices(): array
    {
        $invoices = [];
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);

        // Calculate installment amount based on payment schedule
        $installmentAmount = $this->calculateInstallmentAmount();
        // Calculate subtotal (amount before tax)
        $subtotal = $installmentAmount; // Remove 5% tax
        $taxAmount = $installmentAmount * 0.05;

        // Generate invoices for each period
        $currentDate = $startDate->copy();
        $invoiceNumber = 1;

        while ($currentDate->lessThanOrEqualTo($endDate)) {
            $dueDate = $this->calculateDueDate($currentDate);

            // Create invoice
            $invoice = Invoice::create([
                'contract_id' => $this->id,
                'company_id' => $this->company_id,
                'medical_institution_id' => $this->medical_institution_id,
                'invoice_number' => 'INV-' . $this->contract_number . '-' . str_pad($invoiceNumber, 3, '0', STR_PAD_LEFT),
                'title' => $this->getInvoiceTitle($currentDate),
                'issue_date' => $currentDate,
                'due_date' => $dueDate,
                'status' => 'pending',
                'tax_rate' => 5, // 5% tax
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $installmentAmount+$taxAmount,
            ]);

            // Create invoice item
            $invoice->items()->create([
                'description' => $this->getItemDescription($currentDate),
                'quantity' => 1,
                'unit_price' => $subtotal, // Unit price without tax
                'amount' => $subtotal, // Amount without tax
            ]);

            $invoices[] = $invoice;
            $invoiceNumber++;

            // Move to next period
            $currentDate = $this->getNextInvoiceDate($currentDate);
        }

        return $invoices;
    }

    /**
     * Calculate installment amount based on payment schedule
     */
    protected function calculateInstallmentAmount(): float
    {
        $totalAmount = (float) $this->total_amount;
        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);

        switch ($this->payment_schedule) {
            case 'monthly':
                // Calculate exact number of months between dates
                $months = ceil($startDate->diffInMonths($endDate));
                return round($totalAmount / $months, 2);

            case 'quarterly':
                // Calculate exact number of quarters
                $startQuarter = ceil($startDate->month / 3);
                $endQuarter = ceil($endDate->month / 3);
                $yearDiff = $endDate->year - $startDate->year;
                $quarters = ($yearDiff * 4) + ($endQuarter - $startQuarter) + 1;
                return round($totalAmount / $quarters, 2);

            case 'semi-annually':
                // Calculate exact number of half-years
                $startHalf = $startDate->month <= 6 ? 1 : 2;
                $endHalf = $endDate->month <= 6 ? 1 : 2;
                $yearDiff = $endDate->year - $startDate->year;
                $halfYears = ($yearDiff * 2) + ($endHalf - $startHalf) + 1;
                return round($totalAmount / $halfYears, 2);

            case 'annually':
                // Calculate exact number of years
                $years = $startDate->diffInYears($endDate);

                // If the period spans multiple years, add 1 to include both start and end years
                if ($years > 0) {
                    $years += 1;
                } else {
                    // For same year, check if it's at least a full year
                    $years = $startDate->diffInDays($endDate) >= 365 ? 1 : 1;
                }

                return round($totalAmount / $years, 2);

            default:
                return $totalAmount;
        }
    }

    /**
     * Calculate due date (30 days after issue date)
     */
    protected function calculateDueDate(Carbon $issueDate): Carbon
    {
        return $issueDate->copy()->addDays(30);
    }

    /**
     * Get next invoice date based on payment schedule
     */
    protected function getNextInvoiceDate(Carbon $currentDate): Carbon
    {
        switch ($this->payment_schedule) {
            case 'monthly':
                return $currentDate->copy()->addMonth();

            case 'quarterly':
                return $currentDate->copy()->addMonths(3);

            case 'semi-annually':
                return $currentDate->copy()->addMonths(6);

            case 'annually':
                return $currentDate->copy()->addYear();

            default:
                return $currentDate->copy()->addMonth();
        }
    }

    /**
     * Get invoice title based on period
     */
    protected function getInvoiceTitle(Carbon $date): string
    {
        $period = '';

        switch ($this->payment_schedule) {
            case 'monthly':
                $period = $date->format('F Y');
                break;

            case 'quarterly':
                $quarter = ceil($date->month / 3);
                $period = "Q{$quarter} {$date->year}";
                break;

            case 'semi-annually':
                $half = $date->month <= 6 ? 'First Half' : 'Second Half';
                $period = "{$half} {$date->year}";
                break;

            case 'annually':
                $period = $date->year;
                break;

            default:
                $period = $date->format('F Y');
        }

        return "Invoice for {$period} - Contract {$this->contract_number}";
    }

    /**
     * Get item description based on period
     */
    protected function getItemDescription(Carbon $date): string
    {
        switch ($this->payment_schedule) {
            case 'monthly':
                return "Monthly service fee for " . $date->format('F Y');

            case 'quarterly':
                $quarter = ceil($date->month / 3);
                return "Quarterly service fee for Q{$quarter} {$date->year}";

            case 'semi-annually':
                $half = $date->month <= 6 ? 'First Half' : 'Second Half';
                return "Semi-annual service fee for {$half} {$date->year}";

            case 'annually':
                return "Annual service fee for {$date->year}";

            default:
                return "Service fee for " . $date->format('F Y');
        }
    }

    /**
     * Check if invoices already exist for this contract
     */
    public function hasInvoices(): bool
    {
        return $this->invoices()->exists();
    }
}
