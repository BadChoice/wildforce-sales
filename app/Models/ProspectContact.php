<?php

namespace App\Models;

use Database\Factories\ProspectContactFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'role', 'email', 'phone', 'source_url', 'is_professional', 'contact_status'])]
class ProspectContact extends Model
{
    /** @use HasFactory<ProspectContactFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_professional' => 'boolean',
        ];
    }

    /** @return BelongsTo<Prospect, $this> */
    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }
}
