# Dashboard Widgets

Extra widgets for the Pelican admin dashboard, a custom heading, header links and a configurable layout.

## Widgets

| Widget | Shows | Visible to admins with |
|---|---|---|
| System overview | Panel version and health checks, server count (failed, suspended, installing), nodes online/offline | Any dashboard access. Health needs `view health`, servers and nodes the matching list permission |
| Node details | One row per node: live CPU, memory, swap and disk usage (green / amber at 70 % / red at 90 %), load average, Wings version and OS, a hint when Wings is outdated. Optionally the panel server itself as the first row | Node list permission. The panel server row also needs `view health` |
| Node capacity | Memory, disk and CPU handed out to servers against each node's limit, including overallocation | Node list permission |
| Users & security | Users (new in the last 7 days), share of users with app or email two-factor, failed logins in the last 24 hours | User list permission. Failed logins need `view activityLog` |
| Plugin updates | Installed plugins with an update available | Plugin list permission |
| Egg updates | Eggs whose update URL has a newer version, with their server count | Egg list permission |
| Backups | Backup count and stored size, running backups, failed backups of the last 7 days | Server list permission |
| Schedules | Schedules running for over 15 minutes, then the next 10 due | Server list permission |
| Notes | Shared notes for the whole admin team | Any dashboard access |
| Recent activity | The last 8 activity log entries | `view activityLog` |

Server, node, backup and schedule numbers only count nodes the admin is allowed to access, the same as Pelican's own lists. Pelican's update notice, "Support Pelican" and "Need help?" always sit at the top in one row of three.

## Settings

**Admin → Plugins → Dashboard Widgets → Settings**. Everything applies to all admins and is stored in the panel's `.env` as `DASHBOARD_WIDGETS_*`.

- **Page:** custom heading and description with the placeholders `{greeting}`, `{user}`, `{app}` and `{version}` (empty keeps Pelican's text), up to 5 link buttons next to the heading, and hiding Pelican's canary build notice.
- **Widgets:** per widget on/off, full or half width (two half widgets sit side by side) and an order number (lower comes first; empty uses 10, 20, 30 … in the table order above).
- **Live widgets:** refresh interval for system overview and node details (10 s, 30 s, 1 min or off), and whether to show the panel server row.

Changes to order and links apply on the next page load.

## Notes

- **Panel server row:** read with plain PHP from `/proc` and `disk_total_space` on Linux, no shell commands. CPU usage is measured between two refreshes, so the first load shows "Measuring…". Values that can't be read (another OS, `open_basedir`) are left out.
- **Egg updates:** Pelican checks eggs for updates once a day but keeps the result for only one hour. While the egg widget is enabled, this plugin schedules Pelican's own check (`p:egg:check-updates`) every 30 minutes, so the widget stays accurate. This needs the panel's cron job (`schedule:run`).
- **Node status and usage** come from Wings and are cached by Pelican (status 30 seconds, usage 5 seconds). The live widgets load lazily, so slow nodes don't block the dashboard.
- **Notes** are stored as plain text in the `dashboard_widgets_notes` table. Every admin who can see the dashboard can edit them.
- The layout matches widgets by their class name. If Pelican renames its support or help widget, they simply go back to full width.
