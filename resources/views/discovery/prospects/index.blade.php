<x-layouts::app :title="__('Prospects')">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <flux:heading size="xl">Prospects</flux:heading>
                <flux:text class="mt-1">Every score has a source trail. Discovery does not trigger contact.</flux:text>
            </div>
            <flux:button :href="route('discovery.campaigns.index')" variant="ghost" wire:navigate icon="magnifying-glass">Campaigns</flux:button>
        </div>

        <form method="GET" class="grid gap-3 rounded-xl border border-neutral-200 bg-white p-4 sm:grid-cols-[1fr_auto_auto] dark:border-neutral-700 dark:bg-neutral-900">
            <flux:input name="search" :value="$search" placeholder="Search a business" aria-label="Search prospects" />
            <flux:select name="status" aria-label="Filter by status">
                <option value="">All statuses</option>
                @foreach (\App\Enums\ProspectStatus::cases() as $option)
                    <option value="{{ $option->value }}" @selected($status === $option)>{{ $option->label() }}</option>
                @endforeach
            </flux:select>
            <flux:button type="submit" variant="primary">Filter</flux:button>
        </form>

        <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            @php
                $sortLink = function (string $column) use ($direction, $search, $sort, $status): string {
                    $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';

                    return route('discovery.prospects.index', array_filter([
                        'search' => $search,
                        'status' => $status?->value,
                        'sort' => $column,
                        'direction' => $nextDirection,
                    ], fn ($value): bool => $value !== null && $value !== ''));
                };

                $sortIndicator = function (string $column) use ($direction, $sort): string {
                    if ($sort !== $column) {
                        return '↕';
                    }

                    return $direction === 'asc' ? '↑' : '↓';
                };
            @endphp
            <table class="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-700">
                <thead class="bg-neutral-50 text-left dark:bg-neutral-900">
                    <tr>
                        @foreach ([
                            'business' => 'Business',
                            'location' => 'Location',
                            'status' => 'Status',
                            'enrichment' => 'Enrichment',
                            'outreach' => 'Outreach',
                            'contact' => 'Contact',
                            'progress' => 'Progress',
                            'fit' => 'Fit',
                            'suggested-angle' => 'Suggested angle',
                        ] as $column => $label)
                            <th class="px-5 py-3 font-medium" aria-sort="{{ $sort === $column ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                                <a href="{{ $sortLink($column) }}" wire:navigate class="inline-flex items-center gap-1 hover:text-neutral-950 dark:hover:text-white">
                                    {{ $label }}
                                    <span aria-hidden="true">{{ $sortIndicator($column) }}</span>
                                </a>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-800">
                    @forelse ($prospects as $prospect)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-900">
                            <td class="px-5 py-4">
                                <a href="{{ route('discovery.prospects.show', $prospect) }}" wire:navigate class="font-medium underline decoration-neutral-300 underline-offset-4 hover:decoration-neutral-950 dark:hover:decoration-white">{{ $prospect->name }}</a>
                                <div class="mt-1 text-xs text-neutral-500">{{ str_replace('_', ' ', $prospect->segment) }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="text-neutral-600 dark:text-neutral-300">{{ collect([$prospect->city, $prospect->province])->filter()->join(', ') ?: 'Unknown' }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <flux:badge size="sm" :color="$prospect->status->badgeColor()">{{ $prospect->status->label() }}</flux:badge>
                            </td>
                            <td class="px-5 py-4">
                                @if ($prospect->latestEnrichment === null)
                                    <span class="text-neutral-500">Not started</span>
                                @else
                                    <flux:badge size="sm" :color="$prospect->latestEnrichment->status->badgeColor()">{{ $prospect->latestEnrichment->status->label() }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if ($prospect->latestOutreach === null)
                                    <span class="text-neutral-500">Not started</span>
                                @else
                                    <flux:badge size="sm" :color="$prospect->latestOutreach->status->badgeColor()">{{ $prospect->latestOutreach->status->label() }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                @if ($prospect->replied_at)
                                    <flux:badge size="sm" color="violet">Replied</flux:badge>
                                @elseif ($prospect->contacted_at)
                                    <flux:badge size="sm" color="blue">Contacted</flux:badge>
                                @else
                                    <span class="text-neutral-500">Not contacted</span>
                                @endif
                            </td>
                            <td class="min-w-40 px-5 py-4">
                                <flux:badge size="sm" :color="$prospect->nextStepColor()">{{ $prospect->nextStepLabel() }}</flux:badge>
                                <flux:progress class="mt-2" :color="$prospect->nextStepColor()" style="--flux-progress-percentage: {{ $prospect->pipelineProgress() }}%" />
                            </td>
                            <td class="px-5 py-4">
                                @if ($prospect->score !== null)
                                    <div class="font-medium">{{ $prospect->score }}/100</div>
                                    <div class="mt-1 text-xs text-neutral-500">{{ $prospect->confidence }} confidence</div>
                                @else
                                    <span class="text-neutral-500">Unscored</span>
                                @endif
                            </td>
                            <td class="max-w-md px-5 py-4 text-neutral-600 dark:text-neutral-300">{{ $prospect->suggested_angle ?? 'No angle suggested yet.' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center text-neutral-500">No prospects match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $prospects->links() }}
    </div>
</x-layouts::app>
