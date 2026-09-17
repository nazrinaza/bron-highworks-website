<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoicePayment extends Model
{
    public const METHODS = ['cash' => 'Cash', 'card' => 'Card', 'fpx' => 'FPX'];

    protected $fillable = ['amount_cents', 'method', 'paid_on', 'reference', 'notes', 'recorded_by'];

    protected function casts(): array
    {
        return ['amount_cents' => 'integer', 'paid_on' => 'date'];
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
