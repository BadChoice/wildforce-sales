<?php

namespace App\Models;

use App\Enums\DiscoveryCampaignStatus;
use Database\Factories\DiscoveryCampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['created_by_user_id', 'name', 'segments', 'locations', 'niches', 'language', 'daily_candidate_limit', 'status'])]
class DiscoveryCampaign extends Model
{
    /** @use HasFactory<DiscoveryCampaignFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'segments' => 'array',
            'locations' => 'array',
            'niches' => 'array',
            'status' => DiscoveryCampaignStatus::class,
            'last_run_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return HasMany<DiscoveryRun, $this> */
    public function runs(): HasMany
    {
        return $this->hasMany(DiscoveryRun::class);
    }

    /** @param Builder<DiscoveryCampaign> $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('status', DiscoveryCampaignStatus::Active);
    }
}
