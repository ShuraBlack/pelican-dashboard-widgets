<x-filament-widgets::widget>
    {{-- Pelican caches node statistics for 5 s, so even the shortest refresh never hammers Wings. --}}
    <div @if ($poll) wire:poll.{{ $poll }}s @endif>
        <x-filament::section icon="tabler-server-2" heading="Node details">
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                @if ($panelHost)
                    <div style="border: 1px solid rgb(127 127 127 / 0.25); border-radius: 0.75rem; padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                            <div style="font-weight: 600;">Panel server</div>
                            <div style="display: flex; flex-wrap: wrap; justify-content: end; gap: 0.25rem 1rem; font-size: 0.75rem; opacity: 0.7;">
                                @foreach ($panelHost['meta'] as $meta)
                                    <span>{{ $meta }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: 0.75rem 1.5rem;">
                            @foreach ($panelHost['bars'] as $bar)
                                @include('dashboard-widgets::partials.bar')
                            @endforeach
                        </div>
                    </div>
                @endif

                @forelse ($nodes as $node)
                    <div style="border: 1px solid rgb(127 127 127 / 0.25); border-radius: 0.75rem; padding: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: start; gap: 0.5rem; margin-bottom: 0.75rem;">
                            <div style="min-width: 0;">
                                <div style="font-weight: 600; overflow: hidden; text-overflow: ellipsis;">{{ $node['name'] }}</div>
                                <div style="font-size: 0.75rem; opacity: 0.6; overflow: hidden; text-overflow: ellipsis;">{{ $node['fqdn'] }}</div>
                            </div>
                            <div style="display: flex; align-items: center; flex-wrap: wrap; justify-content: end; gap: 0.25rem 1rem; font-size: 0.75rem;">
                                @if ($node['status'] === 'online')
                                    <span style="opacity: 0.7;">Load: {{ $node['load'] }}</span>
                                    <span style="opacity: 0.7;">{{ $node['system'] }}</span>
                                    @if ($node['wingsOutdated'])
                                        <x-filament::badge color="warning">Wings update available</x-filament::badge>
                                    @endif
                                @endif
                                <x-filament::badge :color="$node['badge']">
                                    {{ ucfirst($node['status']) }}
                                </x-filament::badge>
                            </div>
                        </div>

                        @if ($node['status'] === 'offline')
                            <p style="font-size: 0.875rem; opacity: 0.7;">{{ $node['error'] }}</p>
                        @elseif ($node['status'] === 'maintenance')
                            <p style="font-size: 0.875rem; opacity: 0.7;">Node is in maintenance mode.</p>
                        @else
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr)); gap: 0.75rem 1.5rem;">
                                @foreach ($node['bars'] as $bar)
                                    @include('dashboard-widgets::partials.bar')
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <p style="opacity: 0.7;">No nodes yet.</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
