<x-filament-widgets::widget>
    <x-filament::section icon="tabler-chart-pie" heading="Node capacity" description="Resources handed out to servers, against each node's limit">
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @forelse ($nodes as $node)
                <div style="border: 1px solid rgb(127 127 127 / 0.25); border-radius: 0.75rem; padding: 1rem;">
                    <div style="display: flex; justify-content: space-between; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <span style="font-weight: 600;">{{ $node['name'] }}</span>
                        <span style="font-size: 0.75rem; opacity: 0.7;">{{ trans_choice(':count server|:count servers', $node['servers']) }}</span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: 0.75rem 1.5rem;">
                        @foreach ($node['bars'] as $bar)
                            @include('dashboard-widgets::partials.bar')
                        @endforeach
                    </div>
                </div>
            @empty
                <p style="opacity: 0.7;">No nodes yet.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
