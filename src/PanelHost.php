<?php

namespace ShuraBlack\DashboardWidgets;

use Carbon\CarbonInterface;

// Usage of the machine the panel runs on, read with plain PHP from /proc (Linux) — no shell commands.
// Every metric is optional: unreadable sources (other OS, open_basedir) are simply left out.
class PanelHost
{
    /** @return array{bars: array<int, array<string, mixed>>, meta: array<int, string>}|null */
    public static function stats(): ?array
    {
        $bars = [];
        $meminfo = self::meminfo();
        $cpu = self::cpu();

        if ($cpu !== null) {
            $bars[] = Bars::bar('CPU', $cpu['percent'], $cpu['percent'] === null ? 'Measuring…' : "{$cpu['cores']} cores");
        }

        if (isset($meminfo['MemTotal'], $meminfo['MemAvailable'])) {
            $bars[] = Bars::usage('Memory', $meminfo['MemTotal'] - $meminfo['MemAvailable'], $meminfo['MemTotal']);
        }

        if (($meminfo['SwapTotal'] ?? 0) > 0) {
            $bars[] = Bars::usage('Swap', $meminfo['SwapTotal'] - ($meminfo['SwapFree'] ?? 0), $meminfo['SwapTotal']);
        }

        $diskTotal = @disk_total_space(base_path());
        $diskFree = @disk_free_space(base_path());
        if ($diskTotal && $diskFree !== false) {
            $bars[] = Bars::usage('Disk', $diskTotal - $diskFree, $diskTotal);
        }

        if ($bars === []) {
            return null;
        }

        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : false;
        $uptime = (float) @file_get_contents('/proc/uptime');

        return [
            'bars' => $bars,
            'meta' => array_values(array_filter([
                $load ? sprintf('Load: %.2f / %.2f / %.2f', ...$load) : null,
                $uptime > 0 ? 'Up ' . now()->subSeconds((int) $uptime)->diffForHumans(syntax: CarbonInterface::DIFF_ABSOLUTE, parts: 2) : null,
                'PHP ' . PHP_VERSION,
                gethostname() ?: null,
            ])),
        ];
    }

    /** @return array<string, int> bytes */
    private static function meminfo(): array
    {
        $raw = @file_get_contents('/proc/meminfo');
        if ($raw === false) {
            return [];
        }

        preg_match_all('/^(\w+):\s+(\d+) kB$/m', $raw, $matches);

        return array_map(fn ($kb) => (int) $kb * 1024, array_combine($matches[1], $matches[2]));
    }

    /**
     * CPU usage since the previous dashboard refresh: /proc/stat only holds counters since boot,
     * so the last reading is cached and compared. No sleep needed; the first call returns null.
     *
     * @return array{percent: float|null, cores: int}|null
     */
    private static function cpu(): ?array
    {
        $raw = @file_get_contents('/proc/stat');
        if ($raw === false || !preg_match('/^cpu\s+(.+)$/m', $raw, $match)) {
            return null;
        }

        $fields = array_map('intval', preg_split('/\s+/', trim($match[1])));
        $total = array_sum(array_slice($fields, 0, 8)); // user nice system idle iowait irq softirq steal
        $idle = $fields[3] + ($fields[4] ?? 0);

        $previous = cache()->get('dashboard-widgets.panel-cpu');
        cache()->put('dashboard-widgets.panel-cpu', [$total, $idle], now()->addMinutes(10));

        $percent = null;
        if (is_array($previous) && $total > $previous[0]) {
            $percent = (1 - ($idle - $previous[1]) / ($total - $previous[0])) * 100;
        }

        return ['percent' => $percent, 'cores' => preg_match_all('/^cpu\d+\s/m', $raw)];
    }
}
