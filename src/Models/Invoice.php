<?php

declare(strict_types=1);

namespace Squareetlabs\VeriFactu\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Squareetlabs\VeriFactu\Enums\InvoiceType;

class Invoice extends Model {
    use SoftDeletes;

    public static function boot() {
        parent::boot();

        static::saving(function ($invoice) {
            // Preparar datos para el hash
            $hashData = [
                'issuer_tax_id' => $invoice->issuer_tax_id,
                'invoice_number' => $invoice->number,
                'issue_date' => $invoice->date instanceof \Illuminate\Support\Carbon ? $invoice->date->format('Y-m-d') : $invoice->date,
                'invoice_type' => $invoice->type instanceof \BackedEnum ? $invoice->type->value : (string)$invoice->type,
                'total_tax' => (string)$invoice->tax,
                'total_amount' => (string)$invoice->total,
                'previous_hash' => $invoice->previous_hash ?? '', // Si implementas encadenamiento
                'generated_at' => now()->format('c'),
            ];
            $hashResult = \Squareetlabs\VeriFactu\Helpers\HashHelper::generateInvoiceHash($hashData);
            $invoice->hash = $hashResult['hash'];
            $invoice->uuid = (string) \Illuminate\Support\Str::uuid();
        });

        static::creating(function ($invoice) {
            // Genera un UUID al crear una nueva factura
            $invoice->uuid = (string) \Illuminate\Support\Str::uuid();
        });
    }

    protected $table = 'invoices';

    protected $fillable = [
        'uuid',
        'number',
        'date',
        'customer_name',
        'customer_tax_id',
        'customer_country',
        'issuer_name',
        'issuer_tax_id',
        'issuer_country',
        'amount',
        'tax',
        'total',
        'type',
        'external_reference',
        'description',
        'status',
        'issued_at',
        'cancelled_at',
        'hash',
    ];

    protected $casts = [
        'date' => 'date',
        'type' => InvoiceType::class,
        'amount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function breakdowns()
    {
        return $this->hasMany(Breakdown::class);
    }

    public function recipients()
    {
        return $this->hasMany(Recipient::class);
    }
}
