<div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
    @foreach ($links as $link)
        <x-filament::button tag="a" :href="$link['url']" target="_blank" rel="noopener noreferrer" color="gray" size="sm" icon="tabler-external-link">
            {{ $link['label'] }}
        </x-filament::button>
    @endforeach
</div>
