<?php

namespace ShuraBlack\DashboardWidgets;

// Usage bars shared by the node details, node capacity and panel host views (partials/bar.blade.php).
class Bars
{
    /** @return array{label: string, percent: float, detail: string, color: string} */
    public static function usage(string $label, int|float $used, int|float $total): array
    {
        $detail = convert_bytes_to_readable((int) $used) . ' / ' . convert_bytes_to_readable((int) $total);

        return self::bar($label, $total > 0 ? $used / $total * 100 : 0, $detail);
    }

    /** @return array{label: string, percent: float|null, detail: string, color: string} percent null = no value (shown as "—") */
    public static function bar(string $label, int|float|null $percent, string $detail): array
    {
        if ($percent === null) {
            return ['label' => $label, 'percent' => null, 'detail' => $detail, 'color' => '#10b981'];
        }

        $percent = round(min(100, max(0, $percent)), 1);

        return [
            'label' => $label,
            'percent' => $percent,
            'detail' => $detail,
            // 70 / 90 % are the warning/danger thresholds Pelican uses for its own server cards.
            'color' => $percent >= 90 ? '#ef4444' : ($percent >= 70 ? '#f59e0b' : '#10b981'),
        ];
    }
}
