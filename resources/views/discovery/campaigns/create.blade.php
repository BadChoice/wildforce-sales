<x-layouts::app :title="__('New discovery campaign')">
    <div class="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6">
        <div>
            <flux:heading size="xl">New discovery campaign</flux:heading>
            <flux:text class="mt-1">Define a narrow market. Each daily run discovers up to your selected limit.</flux:text>
        </div>

        <form method="POST" action="{{ route('discovery.campaigns.store') }}" class="flex flex-col gap-5 rounded-xl border border-neutral-200 bg-white p-6 dark:border-neutral-700 dark:bg-neutral-900">
            @csrf
            <flux:input name="name" :value="old('name')" label="Campaign name" placeholder="Personal trainers in Barcelona" required autofocus />
            <flux:input name="segments" :value="old('segments', 'coach')" label="Segments" description="Comma-separated: coach, boutique_gym, gym" required />
            <flux:input name="locations" :value="old('locations', 'Barcelona, Spain')" label="Locations" description="Comma-separated cities or areas" required />
            <flux:input name="niches" :value="old('niches')" label="Niches" description="Optional, comma-separated: strength, functional fitness, rehab" />
            <div class="grid gap-5 sm:grid-cols-2">
                <flux:select name="language" label="Research language" required>
                    <option value="ca" @selected(old('language', 'ca') === 'ca')>Catalan</option>
                    <option value="es" @selected(old('language') === 'es')>Spanish</option>
                </flux:select>
                <flux:input name="daily_candidate_limit" type="number" min="1" max="25" :value="old('daily_candidate_limit', 10)" label="Daily candidate limit" required />
            </div>
            <div class="flex justify-end gap-3">
                <flux:button :href="route('discovery.campaigns.index')" variant="ghost" wire:navigate>Cancel</flux:button>
                <flux:button type="submit" variant="primary">Create campaign</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
