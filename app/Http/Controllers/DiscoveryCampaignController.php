<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDiscoveryCampaignRequest;
use App\Models\DiscoveryCampaign;
use App\Services\Discovery\DiscoveryCampaignService;
use App\Services\Discovery\DiscoveryRunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DiscoveryCampaignController extends Controller
{
    public function index(): View
    {
        return view('discovery.campaigns.index', [
            'campaigns' => DiscoveryCampaign::query()
                ->with('creator')
                ->with(['runs' => fn ($query) => $query->latest()->limit(1)])
                ->withCount('runs')
                ->latest()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('discovery.campaigns.create');
    }

    public function store(StoreDiscoveryCampaignRequest $request, DiscoveryCampaignService $campaigns): RedirectResponse
    {
        $data = $request->validated();
        $campaign = $campaigns->create($request->user(), [
            'name' => $data['name'],
            'segments' => $this->listFrom($data['segments']),
            'locations' => $this->listFrom($data['locations']),
            'niches' => $this->listFrom($data['niches'] ?? ''),
            'language' => $data['language'],
            'daily_candidate_limit' => (int) $data['daily_candidate_limit'],
        ]);

        return to_route('discovery.campaigns.index')
            ->with('status', "Campaign '{$campaign->name}' is active and ready to run.");
    }

    public function run(DiscoveryCampaign $campaign, DiscoveryRunService $runs): RedirectResponse
    {
        $runs->queue($campaign);

        return back()->with('status', "Discovery run queued for '{$campaign->name}'.");
    }

    /** @return array<int, string> */
    private function listFrom(string $value): array
    {
        return array_values(array_filter(array_map(
            fn (string $item): string => trim($item),
            explode(',', $value)
        )));
    }
}
