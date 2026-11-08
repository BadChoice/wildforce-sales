<x-layouts::app :title="$prospect->name">
    <div class="flex w-full flex-1 flex-col gap-6">
        <div class="flex flex-col gap-3">
            <a href="{{ route('discovery.prospects.index') }}" wire:navigate class="text-sm text-neutral-500 hover:text-neutral-950 dark:hover:text-white">← All prospects</a>
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div>
                    <flux:heading size="xl">{{ $prospect->name }}</flux:heading>
                    <flux:text class="mt-1">{{ str_replace('_', ' ', $prospect->segment) }} · {{ collect([$prospect->city, $prospect->province, $prospect->country])->filter()->join(', ') }}</flux:text>
                    <div class="mt-3">
                        <flux:badge size="sm" :color="$prospect->status->badgeColor()">{{ $prospect->status->label() }}</flux:badge>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    @if ($prospect->score !== null)
                        <div class="rounded-xl border border-neutral-200 px-4 py-3 text-right dark:border-neutral-700">
                            <div class="text-2xl font-semibold">{{ $prospect->score }}/100</div>
                            <div class="text-xs text-neutral-500">{{ $prospect->confidence }} confidence</div>
                        </div>
                    @endif

                    @if (in_array($prospect->latestEnrichment?->status?->value, ['queued', 'running'], true))
                        <flux:badge size="sm" :color="$prospect->latestEnrichment->status->badgeColor()">Enrichment {{ $prospect->latestEnrichment->status->label() }}</flux:badge>
                    @else
                        <form method="POST" action="{{ route('discovery.prospects.enrich', $prospect) }}">
                            @csrf
                            <flux:button type="submit" variant="primary" icon="sparkles">Enrich prospect</flux:button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        @if (session('status') || session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    document.dispatchEvent(new CustomEvent('toast-show', {
                        detail: {
                            slots: {
                                text: @js(session('status') ?? session('error')),
                            },
                            dataset: {
                                variant: @js(session('status') ? 'success' : 'danger'),
                            },
                        },
                    }));
                }, { once: true });
            </script>
        @endif

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(22rem,0.72fr)]">
            <section class="flex flex-col gap-4 rounded-xl border border-neutral-200 bg-white p-6 lg:col-start-1 dark:border-neutral-700 dark:bg-neutral-900">
                <div>
                    <flux:heading>Why it fits</flux:heading>
                    <p class="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-300">{{ $prospect->assessments->first()?->rationale ?? 'This prospect has not been assessed yet.' }}</p>
                </div>
                <div>
                    <div class="text-sm font-medium">Suggested angle</div>
                    <p class="mt-1 text-sm leading-6 text-neutral-600 dark:text-neutral-300">{{ $prospect->suggested_angle ?? 'No angle suggested yet.' }}</p>
                </div>
                <div>
                    <div class="text-sm font-medium">Signals</div>
                    <ul class="mt-2 flex list-disc flex-col gap-1 pl-5 text-sm text-neutral-600 dark:text-neutral-300">
                        @forelse ($prospect->signals ?? [] as $signal)
                            <li>{{ $signal }}</li>
                        @empty
                            <li>No signals recorded.</li>
                        @endforelse
                    </ul>
                </div>
            </section>

            <section class="flex flex-col gap-4 rounded-xl border border-neutral-200 bg-white p-6 lg:col-start-1 dark:border-neutral-700 dark:bg-neutral-900">
                <flux:heading>Public profile</flux:heading>
                @if ($prospect->website)
                    <a href="{{ $prospect->website }}" target="_blank" rel="noreferrer" class="text-sm text-blue-600 underline underline-offset-4 dark:text-blue-400">{{ $prospect->website }}</a>
                @endif
                <div class="text-sm text-neutral-600 dark:text-neutral-300">{{ $prospect->public_phone ?? 'No public phone saved.' }}</div>
                @foreach ($prospect->social_profiles ?? [] as $profile)
                    <a href="{{ $profile }}" target="_blank" rel="noreferrer" class="text-sm text-blue-600 underline underline-offset-4 dark:text-blue-400">{{ $profile }}</a>
                @endforeach
            </section>
        <section class="rounded-xl border border-neutral-200 bg-white p-6 lg:col-start-2 lg:row-start-1 dark:border-neutral-700 dark:bg-neutral-900">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div>
                    <flux:heading>First outreach</flux:heading>
                    <flux:text class="mt-1">Generate, review, and send manually. WildForce does not send anything automatically.</flux:text>
                </div>
                @if (($prospect->latestOutreach === null || $prospect->latestOutreach->status->value === 'failed') && $prospect->status->value === 'qualified' && $prospect->latestEnrichment?->status?->value === 'completed')
                    <form method="POST" action="{{ route('discovery.prospects.outreach.generate', $prospect) }}">
                        @csrf
                        <flux:button type="submit" variant="primary" icon="sparkles">Generate outreach</flux:button>
                    </form>
                @elseif ($prospect->latestOutreach !== null)
                    <flux:badge size="sm" :color="$prospect->latestOutreach->status->badgeColor()">{{ $prospect->latestOutreach->status->label() }}</flux:badge>
                @else
                    <flux:badge size="sm" color="zinc">Qualify and complete enrichment first</flux:badge>
                @endif
            </div>

            @if ($prospect->latestOutreach?->status?->value === 'draft' || $prospect->latestOutreach?->status?->value === 'approved' || $prospect->latestOutreach?->status?->value === 'sent')
                @php($outreach = $prospect->latestOutreach)
                <div class="mt-5 grid gap-5 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-neutral-500">Channel</div>
                            <div class="mt-1 text-sm font-medium">{{ str_replace('_', ' ', $outreach->channel) }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-neutral-500">Language</div>
                            <div class="mt-1 text-sm font-medium">{{ strtoupper($outreach->language) }}</div>
                        </div>
                    </div>

                    @if ($outreach->subject)
                        <div>
                            <div class="text-xs font-medium uppercase tracking-wide text-neutral-500">Subject</div>
                            <div class="mt-1 text-sm font-medium">{{ $outreach->subject }}</div>
                        </div>
                    @endif

                    <div>
                        <div class="text-xs font-medium uppercase tracking-wide text-neutral-500">Draft</div>
                        <pre class="mt-2 whitespace-pre-wrap rounded-md bg-neutral-50 p-4 text-sm leading-6 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-200">{{ $outreach->message }}</pre>
                    </div>

                    @if ($outreach->channel_url || $outreach->public_email)
                        <div class="text-sm text-neutral-600 dark:text-neutral-300">
                            {{ $outreach->public_email ?? $outreach->channel_url }}
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-3">
                        <flux:button type="button" variant="ghost" x-data x-on:click="navigator.clipboard.writeText({{ Js::from($outreach->message) }})" icon="clipboard">Copy message</flux:button>

                        @if ($outreach->status->value === 'draft')
                            <form method="POST" action="{{ route('discovery.outreaches.approve', $outreach) }}">
                                @csrf
                                <flux:button type="submit" variant="primary" icon="check">Approve draft</flux:button>
                            </form>
                        @elseif ($outreach->status->value === 'approved')
                            @if ($outreach->channel_url)
                                <flux:button :href="$outreach->channel_url" target="_blank" variant="ghost" icon="arrow-top-right-on-square">Open channel</flux:button>
                            @endif
                            <form method="POST" action="{{ route('discovery.outreaches.mark-sent', $outreach) }}">
                                @csrf
                                <flux:button type="submit" variant="primary" icon="paper-airplane">Mark as sent</flux:button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </section>

        <section class="rounded-xl border border-neutral-200 bg-white p-6 lg:col-start-2 dark:border-neutral-700 dark:bg-neutral-900">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <div>
                    <flux:heading>Replies and follow-ups</flux:heading>
                    <flux:text class="mt-1">Add a reply manually and let AI recommend the next action.</flux:text>
                </div>
                @if ($prospect->follow_up_paused_at)
                    <flux:badge size="sm" color="red">Follow-ups paused</flux:badge>
                @else
                    <form method="POST" action="{{ route('discovery.prospects.follow-ups.pause', $prospect) }}">
                        @csrf
                        <flux:button type="submit" variant="ghost" icon="pause">Pause follow-ups</flux:button>
                    </form>
                @endif
            </div>

            <form method="POST" action="{{ route('discovery.prospects.replies.store', $prospect) }}" class="mt-5 grid gap-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:select name="channel" label="Channel">
                        <option value="email">Email</option><option value="contact_form">Contact form</option><option value="instagram">Instagram</option><option value="linkedin">LinkedIn</option><option value="website">Website</option><option value="other">Other</option>
                    </flux:select>
                    <flux:input type="datetime-local" name="received_at" label="Received at" />
                </div>
                <flux:textarea name="body" label="Reply" placeholder="Paste the prospect's reply here..." required />
                <div><flux:button type="submit" variant="primary" icon="sparkles">Analyze reply</flux:button></div>
            </form>

            @if ($prospect->latestReply)
                <div class="mt-5 rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                    <flux:badge size="sm" :color="$prospect->latestReply->status->badgeColor()">{{ $prospect->latestReply->status->label() }}</flux:badge>
                    @if ($prospect->latestReply->summary)
                        <p class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">{{ $prospect->latestReply->summary }}</p>
                        <p class="mt-2 text-sm font-medium">Recommended: {{ str_replace('_', ' ', $prospect->latestReply->recommended_action) }}</p>
                    @endif
                    @if ($prospect->latestReply->suggested_reply)
                        <pre class="mt-3 whitespace-pre-wrap rounded-md bg-neutral-50 p-4 text-sm dark:bg-neutral-800">{{ $prospect->latestReply->suggested_reply }}</pre>
                    @endif
                </div>
            @endif
        </section>

        <section class="rounded-xl border border-neutral-200 bg-white p-6 lg:col-start-1 dark:border-neutral-700 dark:bg-neutral-900">
            <flux:heading>Sources</flux:heading>
            <div class="mt-4 flex flex-col divide-y divide-neutral-200 dark:divide-neutral-700">
                @forelse ($prospect->observations as $observation)
                    <article class="py-4 first:pt-0 last:pb-0">
                        <a href="{{ $observation->source_url }}" target="_blank" rel="noreferrer" class="font-medium text-blue-600 underline underline-offset-4 dark:text-blue-400">{{ $observation->source_name }}</a>
                        @if ($observation->excerpt)
                            <p class="mt-2 text-sm leading-6 text-neutral-600 dark:text-neutral-300">{{ $observation->excerpt }}</p>
                        @endif
                        <p class="mt-2 text-xs text-neutral-500">Observed {{ $observation->observed_at->diffForHumans() }}</p>
                    </article>
                @empty
                    <p class="text-sm text-neutral-500">No sources were stored.</p>
                @endforelse
            </div>
        </section>
        </div>
    </div>
</x-layouts::app>
