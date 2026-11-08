<?php

namespace App\Console\Commands;

use App\Models\DiscoveryCampaign;
use App\Services\Discovery\DiscoveryRunService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('discovery:run-scheduled')]
#[Description('Queue every active discovery campaign that has not run today.')]
class RunScheduledDiscoveryCampaigns extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DiscoveryRunService $runs): int
    {
        $campaigns = DiscoveryCampaign::query()
            ->active()
            ->where(fn ($query) => $query->whereNull('last_run_at')->orWhereDate('last_run_at', '<', today()))
            ->get();

        $campaigns->each(fn (DiscoveryCampaign $campaign) => $runs->queue($campaign));

        $this->components->info("Queued {$campaigns->count()} discovery campaign(s).");

        return self::SUCCESS;
    }
}
