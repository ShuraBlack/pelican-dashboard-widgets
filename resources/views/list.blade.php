{{-- Shared list card: plugin/egg updates, backups, schedules. Items: name, detail, optional badge [text, color]. --}}
<x-filament-widgets::widget>
    <x-filament::section :icon="$icon" :heading="$heading" :description="$description ?? null">
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
                        @isset($item['badge'])
                            <x-filament::badge :color="$item['badge'][1]">{{ $item['badge'][0] }}</x-filament::badge>
                        @endisset
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
