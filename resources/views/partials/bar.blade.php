<div>
    <div style="display: flex; justify-content: space-between; font-size: 0.875rem;">
        <span style="font-weight: 500;">{{ $bar['label'] }}</span>
        <span style="font-weight: 600;">{{ $bar['percent'] === null ? '—' : $bar['percent'] . '%' }}</span>
    </div>
    <div role="progressbar" aria-label="{{ $bar['label'] }}" aria-valuenow="{{ $bar['percent'] ?? 0 }}" aria-valuemin="0" aria-valuemax="100"
         style="height: 0.5rem; border-radius: 999px; background: rgb(127 127 127 / 0.2); margin: 0.25rem 0; overflow: hidden;">
        <div style="height: 100%; width: {{ $bar['percent'] ?? 0 }}%; background: {{ $bar['color'] }}; border-radius: 999px; transition: width 0.5s;"></div>
    </div>
    <div style="font-size: 0.75rem; opacity: 0.6;">{{ $bar['detail'] }}</div>
</div>
