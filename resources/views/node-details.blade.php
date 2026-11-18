{{-- Polls every 10 s; Pelican caches node statistics for 5 s, so this never hammers Wings. --}}
<x-filament-widgets::widget wire:poll.10s>
    <x-filament::section icon="tabler-server-2" heading="Node details">
        @if ($nodes->isEmpty())
            <p style="opacity: 0.7;">No nodes yet.</p>
        @endif

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(17rem, 1fr)); gap: 1rem;">
            @foreach ($nodes as $node)
                <div style="border: 1px solid rgb(127 127 127 / 0.25); border-radius: 0.75rem; padding: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: start; gap: 0.5rem; margin-bottom: 0.75rem;">
                        <div style="min-width: 0;">
                            <div style="font-weight: 600; overflow: hidden; text-overflow: ellipsis;">{{ $node['name'] }}</div>
                            <div style="font-size: 0.75rem; opacity: 0.6; overflow: hidden; text-overflow: ellipsis;">{{ $node['fqdn'] }}</div>
                        </div>
                        <x-filament::badge :color="$node['badge']">
                            {{ ucfirst($node['status']) }}
                        </x-filament::badge>
                    </div>

                    @if ($node['status'] === 'offline')
                        <p style="font-size: 0.875rem; opacity: 0.7;">{{ $node['error'] }}</p>
                    @elseif ($node['status'] === 'maintenance')
                        <p style="font-size: 0.875rem; opacity: 0.7;">Node is in maintenance mode.</p>
                    @else
                        @foreach ($node['bars'] as $bar)
                            <div style="margin-bottom: 0.75rem;">
                                <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
                                    <span style="font-weight: 500;">{{ $bar['label'] }}</span>
                                    <span style="font-weight: 600;">{{ $bar['percent'] }}%</span>
                                </div>
                                <div role="progressbar" aria-label="{{ $bar['label'] }}" aria-valuenow="{{ $bar['percent'] }}" aria-valuemin="0" aria-valuemax="100"
                                     style="height: 0.5rem; border-radius: 999px; background: rgb(127 127 127 / 0.2); margin: 0.25rem 0; overflow: hidden;">
                                    <div style="height: 100%; width: {{ $bar['percent'] }}%; background: {{ $bar['color'] }}; border-radius: 999px; transition: width 0.5s;"></div>
                                </div>
                                <div style="font-size: 0.75rem; opacity: 0.6;">{{ $bar['detail'] }}</div>
                            </div>
                        @endforeach

                        <div style="font-size: 0.75rem; opacity: 0.7; display: flex; flex-wrap: wrap; gap: 0.25rem 1rem;">
                            <span>Load: {{ $node['load'] }}</span>
                            <span>{{ $node['system'] }}</span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
