<?php

// Saved by the plugin settings as DASHBOARD_WIDGETS_* in the panel's .env.
// List values are URL-encoded JSON, because the .env writer works line by line.

$widget = fn (string $key, string $width = 'full') => [
    'enabled' => env("DASHBOARD_WIDGETS_{$key}", true),
    'width' => env("DASHBOARD_WIDGETS_{$key}_WIDTH", $width),
    'order' => env("DASHBOARD_WIDGETS_{$key}_ORDER"),
];

return [
    'widgets' => [
        'system' => $widget('SYSTEM'),
        'node_details' => $widget('NODE_DETAILS'),
        'node_capacity' => $widget('NODE_CAPACITY', 'half'),
        'allocations' => $widget('ALLOCATIONS', 'half'),
        'users' => $widget('USERS'),
        'problem_servers' => $widget('PROBLEM_SERVERS', 'half'),
        'recent_servers' => $widget('RECENT_SERVERS', 'half'),
        'plugin_updates' => $widget('PLUGIN_UPDATES', 'half'),
        'egg_updates' => $widget('EGG_UPDATES', 'half'),
        'backups' => $widget('BACKUPS', 'half'),
        'schedules' => $widget('SCHEDULES', 'half'),
        'transfers' => $widget('TRANSFERS', 'half'),
        'failed_jobs' => $widget('FAILED_JOBS', 'half'),
        'notes' => $widget('NOTES'),
        'activity' => $widget('ACTIVITY'),
    ],

    'panel_host' => env('DASHBOARD_WIDGETS_PANEL_HOST', true),
    'hide_canary' => env('DASHBOARD_WIDGETS_HIDE_CANARY', false),
    'refresh' => env('DASHBOARD_WIDGETS_REFRESH', '30'),

    'heading' => env('DASHBOARD_WIDGETS_HEADING'),
    'subheading' => env('DASHBOARD_WIDGETS_SUBHEADING'),

    'links' => json_decode(rawurldecode((string) env('DASHBOARD_WIDGETS_LINKS', '')), true) ?: [],
];
