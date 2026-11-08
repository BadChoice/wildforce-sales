<x-layouts::app :title="__('Discovery campaigns')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <flux:heading size="xl">Discovery campaigns</flux:heading>
                <flux:text class="mt-1">The agent only saves sourced prospects. It does not contact anyone.</flux:text>
            </div>
            <flux:button :href="route('discovery.campaigns.create')" variant="primary" wire:navigate icon="plus">New campaign</flux:button>
        </div>

        @php($hasActiveRun = $campaigns->contains(fn ($campaign): bool => in_array($campaign->runs->first()?->status?->value, ['queued', 'running'], true)))

        <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <table class="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-700">
                <thead class="bg-neutral-50 text-left dark:bg-neutral-900">
                    <tr>
                        <th class="px-5 py-3 font-medium">Campaign</th>
                        <th class="px-5 py-3 font-medium">Scope</th>
                        <th class="px-5 py-3 font-medium">Daily limit</th>
                        <th class="px-5 py-3 font-medium">Last run</th>
                        <th class="px-5 py-3 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                    @forelse ($campaigns as $campaign)
                        @php($latestRun = $campaign->runs->first())
                        <tr>
                            <td class="px-5 py-4">
                                <div class="font-medium">{{ $campaign->name }}</div>
                                <div class="mt-1 text-xs text-neutral-500">{{ ucfirst($campaign->status->value) }} · {{ $campaign->runs_count }} runs</div>
                            </td>
                            <td class="px-5 py-4 text-neutral-600 dark:text-neutral-300">
                                <div>{{ implode(', ', $campaign->segments) }}</div>
                                <div class="mt-1 text-xs text-neutral-500">{{ implode(', ', $campaign->locations) }}</div>
                            </td>
                            <td class="px-5 py-4">{{ $campaign->daily_candidate_limit }}</td>
                            <td class="px-5 py-4">
                                @if ($latestRun === null)
                                    <span class="text-neutral-500">Not run yet</span>
                                @elseif ($latestRun->status->value === 'queued')
                                    <span class="font-medium text-amber-700 dark:text-amber-300">Queued</span>
                                @elseif ($latestRun->status->value === 'running')
                                    <span class="font-medium text-blue-700 dark:text-blue-300">Running</span>
                                @elseif ($latestRun->status->value === 'failed')
                                    <span class="font-medium text-red-700 dark:text-red-300">Failed</span>
                                @else
                                    <span class="text-neutral-600 dark:text-neutral-300">Completed {{ $latestRun->completed_at?->diffForHumans() }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <form method="POST" action="{{ route('discovery.campaigns.run', $campaign) }}">
                                    @csrf
                                    <flux:button type="submit" size="sm" variant="primary" icon="play">Run now</flux:button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-neutral-500">Create a campaign to start sourced discovery.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if (session('status'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.dispatchEvent(new CustomEvent('toast-show', {
                    detail: {
                        slots: {
                            text: @js(session('status')),
                        },
                        dataset: {
                            variant: 'success',
                        },
                    },
                }));
            }, { once: true });
        </script>
    @endif

    @if ($hasActiveRun)
        <script>
            window.setTimeout(() => window.location.reload(), 5000);
        </script>
    @endif
</x-layouts::app>
