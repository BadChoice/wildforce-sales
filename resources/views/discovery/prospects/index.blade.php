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
                    $nextDirection = $sort === $column && $direction === \SortDirection::Ascending ? 'desc' : 'asc';

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

                    return $direction === \SortDirection::Ascending ? '↑' : '↓';
                };
            @endphp
            <table class="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-700">
                <thead class="bg-neutral-50 text-left dark:bg-neutral-900">
                    <tr>
                        @foreach ([
                            'business' => 'Business',
                            'location' => 'Location',
                            'stage' => 'Stage',
                            'fit' => 'Fit',
                            'suggested-angle' => 'Suggested angle',
                        ] as $column => $label)
                            <th class="px-5 py-3 font-medium" aria-sort="{{ $sort === $column ? ($direction === \SortDirection::Ascending ? 'ascending' : 'descending') : 'none' }}">
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
                            <td class="min-w-48 px-5 py-4">
                                @php($stage = $prospect->pipelineStage())
                                <flux:tooltip :content="$prospect->pipelineTooltip()" position="top">
                                    <div class="inline-flex cursor-help flex-col gap-2">
                                        <flux:badge size="sm" :color="$stage->color()" :icon="$stage->icon()">{{ $stage->label() }}</flux:badge>
                                        <div class="h-1.5 w-40 overflow-hidden rounded-full bg-neutral-200 dark:bg-white/10">
                                            <div @class([
                                                'h-full rounded-full transition-[width] duration-300',
                                                'bg-zinc-600 dark:bg-zinc-400' => $stage->color() === 'zinc',
                                                'bg-blue-600 dark:bg-blue-400' => $stage->color() === 'blue',
                                                'bg-emerald-600 dark:bg-emerald-400' => $stage->color() === 'emerald',
                                                'bg-red-600 dark:bg-red-400' => $stage->color() === 'red',
                                                'bg-violet-600 dark:bg-violet-400' => $stage->color() === 'violet',
                                                'bg-indigo-600 dark:bg-indigo-400' => $stage->color() === 'indigo',
                                                'bg-sky-600 dark:bg-sky-400' => $stage->color() === 'sky',
                                                'bg-purple-600 dark:bg-purple-400' => $stage->color() === 'purple',
                                            ]) style="width: {{ $stage->progress() }}%"></div>
                                        </div>
                                    </div>
                                </flux:tooltip>
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
                            <td colspan="5" class="px-5 py-12 text-center text-neutral-500">No prospects match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $prospects->links() }}
    </div>
</x-layouts::app>
