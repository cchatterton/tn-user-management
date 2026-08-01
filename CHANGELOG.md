# Changelog

All notable changes to TN User Management are recorded here.

## 1.12 - 2026-08-01

- Enforced Administrator access for the activating user before role processing begins.
- Granted the activating user Super Admin access and Administrator membership on every existing site in multisite.
- Re-established access after username migration and refreshed changed-login authentication cookies to prevent activation logout.
- Kept multisite's login-based Super Admin list aligned when usernames are migrated to email addresses.
- Limited username migration execution to once per activation request and made its result notice display only once.
- Removed activation warnings caused by requesting unsupported role fields from WordPress user queries.

## 1.11 - 2026-08-01

- Added a dedicated Custom capability group for capabilities created through the plugin.
- Added per-user All content and Own content only controls to the Permission Sets profile block.
- Enforced ownership restrictions across all public post types while preserving read access to other authors' content.

## 1.10 - 2026-08-01

- Aligned capability columns consistently across every grouped table.
- Replaced the visible Action heading and empty placeholders with a blank utility column.
- Replaced capability removal buttons with accessible trash icons.
- Added quick links to every capability section below the role summary.

## 1.9 - 2026-08-01

- Added a fourth, database-only Integration role with no capabilities or login access.
- Renamed the Roles page to Capabilities and added capability creation and removal controls.
- Added prefix-based capability table grouping, with ungrouped capabilities shown first.
- Added View details, GitHub, and nonce-protected Check for updates plugin-row links.
- Brought the GitHub updater metadata, caching, forced checks, native update injection, diagnostics, and post-update cleanup in line with the Codex standards.
- Tightened nonce handling, admin asset loading, and accessibility markup.

## 1.8 - 2026-06-14

- Updated the GitHub release updater to clear stale same-version update notices.
- Updated forced WordPress update checks to bypass the plugin-specific GitHub release cache.

## 1.7 - 2026-06-14

- Added a generated root `tn-user-management.zip` for direct WordPress plugin upload.
- Updated the release build script to publish the same ZIP to `dist/` and the repository root.

## 1.6 - 2026-06-14

- Added GitHub release update support for native WordPress plugin updates.
- Added compliant plugin metadata, version constant, and GitHub repository links.
- Renamed the plugin package folder to `tn-user-management` for release ZIP compatibility.
- Moved inline admin JavaScript and CSS into dedicated asset files.
- Improved request nonce handling for manual sync and notice dismissal actions.

## 1.5 - 2026-06-11

- Resolved missing items from permission sets.

## 1.4 - 2026-05-14

- Resolved absent role issues for Administrator and Subscriber.

## 1.3 - 2026-03-28

- Added manual Sync Admin Rights action and activation-only user role sync.

## 1.2 - 2026-03-28

- Added baseline role migration and permission sets support.

## 1.1 - 2026-03-28

- Rebuilt as TN User Management with activation migration and multisite sync.
