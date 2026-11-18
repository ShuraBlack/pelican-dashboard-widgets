# Dashboard Widgets

Extra widgets for the Pelican admin dashboard, plus a tidier layout.

| Widget | Shows | Visible to admins with |
|---|---|---|
| System overview | Panel version and health checks, server count (failed, suspended, installing), nodes online/offline | Any dashboard access. Health needs `view health`, servers and nodes the matching list permission |
| Node details | One card per node: live CPU, memory, swap and disk usage (green / amber at 70 % / red at 90 %), load average, Wings version and OS | Node list permission |
| Plugin updates | Installed plugins with an update available | Plugin list permission |
| Egg updates | Eggs whose update URL has a newer version, with their server count | Egg list permission |
| Notes | Shared notes for the whole admin team | Any dashboard access |
| Recent activity | The last 8 activity log entries | `view activityLog` |

Server and node numbers only count nodes the admin is allowed to access, the same as Pelican's own lists.

## Layout

From top to bottom: Pelican's update widget, system overview, node details, plugin and egg updates side by side, notes, recent activity, and Pelican's "Support Pelican" and "Need help?" as two compact cards side by side. Below 768 px everything stacks.

## Settings

**Admin → Plugins → Dashboard Widgets → Settings** has one toggle per widget. Settings are stored in the panel's `.env` as `DASHBOARD_WIDGETS_*` and apply to all admins.

## Notes

- **Egg updates:** Pelican checks eggs for updates once a day but keeps the result for only one hour. While the egg widget is enabled, this plugin schedules Pelican's own check (`p:egg:check-updates`) every 30 minutes, so the widget stays accurate. This needs the panel's cron job (`schedule:run`).
- **Node status and usage** come from Wings and are cached by Pelican (status 30 seconds, usage 5 seconds). The system overview refreshes every 30 seconds, node details every 10 seconds. Both load lazily, so slow nodes don't block the dashboard.
- **Notes** are stored as plain text in the `dashboard_widgets_notes` table. Every admin who can see the dashboard can edit them.
- The layout matches widgets by their class name. If Pelican renames its support or help widget, they simply go back to full width.
