<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">WildForce Growth</flux:heading>
            <flux:text class="mt-2">Discover, qualify, and document the businesses that best fit WildForce.</flux:text>
        </div>

        @can('access-growth')
            <div class="grid gap-4 md:grid-cols-2">
                <a href="{{ route('discovery.campaigns.index') }}" wire:navigate class="rounded-xl border border-neutral-200 bg-white p-6 transition hover:border-neutral-300 dark:border-neutral-700 dark:bg-neutral-900 dark:hover:border-neutral-600">
                    <flux:heading>Discovery campaigns</flux:heading>
                    <flux:text class="mt-2">Set the market, profile, and daily limit for AI-assisted research.</flux:text>
                </a>
                <a href="{{ route('discovery.prospects.index') }}" wire:navigate class="rounded-xl border border-neutral-200 bg-white p-6 transition hover:border-neutral-300 dark:border-neutral-700 dark:bg-neutral-900 dark:hover:border-neutral-600">
                    <flux:heading>Prospect workspace</flux:heading>
                    <flux:text class="mt-2">Review sourced evidence, ICP score, and the recommended angle for every prospect.</flux:text>
                </a>
            </div>
        @else
            <flux:text>Your account does not have access to the internal growth workspace.</flux:text>
        @endcan
    </div>
</x-layouts::app>
