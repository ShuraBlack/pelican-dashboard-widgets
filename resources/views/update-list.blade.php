{{-- Shared by the plugin and egg update widgets. --}}
<x-filament-widgets::widget>
    <x-filament::section :icon="$icon" :heading="$heading">
        @if ($url)
            <x-slot name="afterHeader">
                <x-filament::link :href="$url" size="sm">{{ $linkLabel }}</x-filament::link>
            </x-slot>
        @endif

        @if ($items->isEmpty())
            <p style="opacity: 0.7;">{{ $empty }}</p>
        @else
            {{-- Scrolls instead of growing with long lists; tabindex makes it scrollable by keyboard. --}}
            <div tabindex="0" style="max-height: 15rem; overflow-y: auto; padding-right: 0.5rem;">
                @foreach ($items as $item)
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 0.25rem 0;">
                        <span>{{ $item['name'] }} <span style="opacity: 0.6;">{{ $item['detail'] }}</span></span>
                        <x-filament::badge color="warning">Update available</x-filament::badge>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
