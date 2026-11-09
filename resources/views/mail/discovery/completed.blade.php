<x-mail::message>
# Discovery completed

**Campaign:** {{ $run->campaign->name }}  
**Prospects processed:** {{ $run->discovered_count }}  
**Completed at:** {{ $run->completed_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}

@php($candidates = $run->result['candidates'] ?? [])

@if ($candidates !== [])
## Candidates found

@foreach ($candidates as $candidate)
- **{{ $candidate['business_name'] ?? 'Unnamed prospect' }}**
  @if (filled($candidate['segment'] ?? null))
  — {{ str_replace('_', ' ', $candidate['segment']) }}
  @endif
  @if (filled($candidate['city'] ?? null))
  · {{ $candidate['city'] }}
  @endif
  @if (filled($candidate['sources'][0]['url'] ?? null))
  · [Source]({{ $candidate['sources'][0]['url'] }})
  @endif
@endforeach
@else
No candidates were returned by this run.
@endif

<x-mail::button :url="route('discovery.campaigns.index')">
Open discovery campaigns
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
