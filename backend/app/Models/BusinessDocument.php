<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessDocument extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'site_visit_id', 'parent_id', 'status', 'party_name', 'party_address', 'party_email', 'external_reference', 'issued_on', 'due_on', 'items', 'subtotal_cents', 'tax_basis_points', 'tax_cents', 'total_cents', 'notes'];

    public const TYPES = ['quotation' => 'Quotation', 'customer_po' => 'Customer purchase order', 'supplier_po' => 'Supplier purchase order', 'invoice' => 'Invoice', 'delivery_order' => 'Delivery order'];

    public const PREFIXES = ['quotation' => 'QUO', 'customer_po' => 'CPO', 'supplier_po' => 'SPO', 'invoice' => 'INV', 'delivery_order' => 'DO'];

    public static function statuses(string $type): array
    {
        return match ($type) {
            'quotation' => ['draft', 'issued', 'accepted', 'declined', 'cancelled'],
            'customer_po' => ['draft', 'received', 'confirmed', 'fulfilled', 'cancelled'],
            'supplier_po' => ['draft', 'issued', 'received', 'cancelled'],
            'invoice' => ['draft', 'issued', 'partially_paid', 'paid', 'cancelled'],
            'delivery_order' => ['draft', 'issued', 'delivered', 'cancelled'], default => ['draft']
        };
    }

    protected function casts(): array
    {
        return ['revision' => 'integer', 'items' => 'array', 'issued_on' => 'date', 'due_on' => 'date'];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderBy('paid_on')->orderBy('id');
    }

    public function recordedPaidCents(): int
    {
        return (int) $this->payments->sum('amount_cents');
    }

    public function balanceCents(): int
    {
        return max(0, (int) $this->total_cents - $this->recordedPaidCents());
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(SiteVisit::class, 'site_visit_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public static function cents(string $value): int
    {
        $parts = explode('.', $value, 2);

        return (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
    }
}
