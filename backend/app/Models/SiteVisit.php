<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SiteVisit extends Model
{
    use HasFactory;

    protected $fillable = ['reference', 'name', 'company', 'email', 'phone', 'service', 'address', 'area', 'preferred_date', 'access_details', 'requirements', 'status', 'scheduled_at', 'assigned_to', 'internal_notes', 'assessment'];

    public const STATUSES = ['new', 'contacted', 'scheduled', 'completed', 'cancelled'];

    public const SERVICES = ['Building & façade cleaning', 'Solar panel cleaning', 'Both'];

    protected function casts(): array
    {
        return ['preferred_date' => 'date', 'scheduled_at' => 'datetime'];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BusinessDocument::class);
    }
}
