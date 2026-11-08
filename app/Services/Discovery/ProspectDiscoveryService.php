<?php

namespace App\Services\Discovery;

use App\Enums\LeadActivityType;
use App\Enums\ProspectStatus;
use App\Models\DiscoveryRun;
use App\Models\LeadActivity;
use App\Models\Prospect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProspectDiscoveryService
{
    /**
     * @param  array<int, array<string, mixed>>  $candidates
     */
    public function storeCandidates(DiscoveryRun $run, array $candidates): int
    {
        $stored = 0;

        foreach ($candidates as $candidate) {
            $this->storeCandidate($run, $candidate);
            $stored++;
        }

        return $stored;
    }

    /** @param array<string, mixed> $candidate */
    private function storeCandidate(DiscoveryRun $run, array $candidate): void
    {
        $candidate['country'] = $this->countryCode($candidate['country'] ?? null);

        $candidate = Validator::validate($candidate, [
            'business_name' => ['required', 'string', 'max:255'],
            'segment' => ['required', 'in:coach,boutique_gym,gym'],
            'website' => ['nullable', 'url', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'size:2'],
            'public_phone' => ['nullable', 'string', 'max:255'],
            'social_profiles' => ['present', 'array'],
            'social_profiles.*' => ['url', 'max:255'],
            'detected_services' => ['present', 'array'],
            'detected_services.*' => ['string', 'max:255'],
            'signals' => ['present', 'array'],
            'signals.*' => ['string', 'max:500'],
            'score' => ['required', 'integer', 'between:0,100'],
            'confidence' => ['required', 'in:low,medium,high'],
            'rationale' => ['required', 'string', 'max:2000'],
            'suggested_angle' => ['nullable', 'string', 'max:2000'],
            'sources' => ['required', 'array', 'min:1'],
            'sources.*.name' => ['required', 'string', 'max:255'],
            'sources.*.url' => ['required', 'url', 'max:2000'],
            'sources.*.excerpt' => ['nullable', 'string', 'max:4000'],
        ]);

        DB::transaction(function () use ($run, $candidate): void {
            $domain = $this->domainFor($candidate['website']);
            $prospect = Prospect::firstOrCreate(
                ['canonical_key' => $this->canonicalKey($candidate['business_name'], $candidate['city'], $domain)],
                ['name' => $candidate['business_name'], 'segment' => $candidate['segment']]
            );

            $status = $prospect->wasRecentlyCreated
                ? ProspectStatus::Discovered
                : $prospect->status;

            $prospect->update([
                'name' => $candidate['business_name'],
                'segment' => $candidate['segment'],
                'website' => $candidate['website'],
                'domain' => $domain,
                'city' => $candidate['city'],
                'province' => $candidate['province'],
                'country' => Str::upper($candidate['country']),
                'public_phone' => $candidate['public_phone'],
                'social_profiles' => $candidate['social_profiles'],
                'status' => $status,
                'score' => $candidate['score'],
                'confidence' => $candidate['confidence'],
                'suggested_angle' => $candidate['suggested_angle'] ?? null,
                'detected_services' => $candidate['detected_services'],
                'signals' => $candidate['signals'],
                'last_discovered_at' => now(),
            ]);

            foreach ($candidate['sources'] as $source) {
                $prospect->observations()->firstOrCreate([
                    'source_hash' => hash('sha256', $source['url'].'|'.($source['excerpt'] ?? '')),
                ], [
                    'discovery_run_id' => $run->id,
                    'source_name' => $source['name'],
                    'source_url' => $source['url'],
                    'excerpt' => $source['excerpt'] ?? null,
                    'facts' => [
                        'signals' => $candidate['signals'],
                        'detected_services' => $candidate['detected_services'],
                    ],
                    'observed_at' => now(),
                ]);
            }

            $prospect->assessments()->create([
                'discovery_run_id' => $run->id,
                'score' => $candidate['score'],
                'confidence' => $candidate['confidence'],
                'rationale' => $candidate['rationale'],
                'suggested_angle' => $candidate['suggested_angle'] ?? null,
                'detected_services' => $candidate['detected_services'],
                'signals' => $candidate['signals'],
                'evidence' => $candidate['sources'],
            ]);

            LeadActivity::create([
                'prospect_id' => $prospect->id,
                'discovery_run_id' => $run->id,
                'type' => $prospect->wasRecentlyCreated ? LeadActivityType::Discovered : LeadActivityType::Enriched,
                'metadata' => ['score' => $candidate['score']],
                'occurred_at' => now(),
            ]);
        });
    }

    private function canonicalKey(string $name, ?string $city, ?string $domain): string
    {
        if ($domain !== null) {
            return 'domain:'.$domain;
        }

        return 'business:'.Str::slug($name.' '.($city ?? 'spain'));
    }

    private function domainFor(?string $website): ?string
    {
        if ($website === null) {
            return null;
        }

        $host = parse_url($website, PHP_URL_HOST);

        return is_string($host) ? Str::lower(Str::after($host, 'www.')) : null;
    }

    private function countryCode(mixed $country): ?string
    {
        if (! is_string($country)) {
            return null;
        }

        return match (Str::upper(Str::ascii($country))) {
            'ESPANA', 'SPAIN' => 'ES',
            default => Str::upper($country),
        };
    }
}
