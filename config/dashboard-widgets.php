<?php

// Saved by the plugin settings as DASHBOARD_WIDGETS_<KEY> ("true"/"false") in the panel's .env.
return [
    'system' => env('DASHBOARD_WIDGETS_SYSTEM', true),
    'node_details' => env('DASHBOARD_WIDGETS_NODE_DETAILS', true),
    'plugin_updates' => env('DASHBOARD_WIDGETS_PLUGIN_UPDATES', true),
    'egg_updates' => env('DASHBOARD_WIDGETS_EGG_UPDATES', true),
    'notes' => env('DASHBOARD_WIDGETS_NOTES', true),
    'activity' => env('DASHBOARD_WIDGETS_ACTIVITY', true),
];
