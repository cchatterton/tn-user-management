# Changelog

All notable changes to TN User Management are recorded here.

## 1.32 - 2026-08-31

- Enforced Skip Confirmation Email for both Add Existing User and Add User on multisite subsite screens.
- Kept both subsite controls visibly checked and disabled to reflect the enforced server-side behaviour.
- Updated subsite help text so it no longer promises confirmation or welcome email.

## 1.31 - 2026-08-31

- Disabled WordPress account emails to newly created users while retaining administrator notifications.
- Disabled the single-site new-user notification checkbox to reflect the enforced behaviour.
- Updated Add User screen text, including Network Admin, so it no longer promises an account email.

## 1.30 - 2026-08-27

- Declared GPL v2-or-later licensing in the plugin header and package metadata.
- Added a GPL v2-or-later licence file to the distributable plugin.
- Added a WordPress.org-compatible `readme.txt` with verified contributor, compatibility, installation, safety, FAQ, external-service, and changelog information.

## 1.29 - 2026-08-27

- Displayed generated capability quicklinks and group headings with an uppercase first character while preserving their lowercase internal keys.

## 1.28 - 2026-08-27

- Activated the final capability navigation link when the viewport reaches the bottom of the page.
- Fixed the last group never becoming active when its heading cannot reach the normal scroll activation line.
- Added viewport-relative blank space beneath the final capability group so it can scroll naturally into the active position.
- Made section links calculate their jump position from the live WordPress toolbar and sticky-menu heights, placing the selected heading directly beneath the menu.
- Left the navigation unselected before the first capability section is reached, including after scrolling back to the top.

## 1.27 - 2026-08-27

- Aligned the sticky navigation activation threshold with each capability heading's anchor scroll offset.
- Fixed section links highlighting the preceding group after being clicked.
- Replaced the filled active-link treatment with passive grey links and understated blue active text, without underlines or backgrounds.

## 1.26 - 2026-08-27

- Removed the visible `Sections:` label from the sticky capability navigation while retaining its accessible navigation label.

## 1.25 - 2026-08-27

- Added scroll-aware highlighting to the sticky capability Sections navigation.
- Added `aria-current="location"` to the active section link and kept the state synchronized during scrolling and anchor navigation.

## 1.24 - 2026-08-27

- Removed the Administrator, User, and Subscriber registered-capability summary from the sticky navigation.
- Removed the Subscriber column from every capability table without changing the underlying Subscriber role.
- Rebalanced the remaining capability, Administrator, User, and action columns.

## 1.23 - 2026-08-27

- Removed the border and box shadow from the capability navigator in both normal and fixed states.
- Applied consistent `1rem 0` padding and a `#e4e0e4` background before and after the navigator becomes fixed.
- Removed fixed-state horizontal padding so the navigator content retains its original alignment.

## 1.22 - 2026-08-27

- Added a WordPress-toolbar-aware fixed-position fallback so the role summary and Sections navigator remain visible in admin layouts where native sticky positioning is ineffective.
- Preserved the navigator's original page space and content width to prevent jumping or horizontal misalignment while it is fixed.

## 1.21 - 2026-08-27

- Removed borders and button chrome from individual and group capability trash controls.
- Changed trash icons to subtle gray by default with red hover and focus feedback.
- Moved group trash icons directly inline beside their group headings.
- Made the role summary and Sections navigation a compact sticky bar below the WordPress admin toolbar, with anchor offsets for unobscured group headings.
- Kept one-item prefixes in Single capabilities, creating a named group only when at least two capabilities share that prefix.

## 1.20 - 2026-08-27

- Added an AJAX trash action to every capability row so individual capabilities, including unprefixed Singles, can be removed from every stored role without reloading the page.
- Replaced group-level text removal buttons with compact, right-aligned trash icons and explicit accessible labels.
- Kept destructive confirmations, nonce validation, permission checks, role counts, and capability tracking in sync after removal.
- Normalised group headings to the prefix without `_*` and made group removal include both the exact prefix capability and all underscore-prefixed members.
- Applied the same clean prefix names to the Sections navigation and retained one-capability underscore groups instead of moving them into Singles.

## 1.19 - 2026-08-27

- Replaced the heavy filled capability buttons with compact rounded switch tracks and understated Yes/No labels using the native WordPress admin-theme colour.
- Removed the conflicting WordPress button-link styling that caused the disabled switch to render incorrectly.
- Added reduced-motion support while retaining keyboard focus and screen-reader switch semantics.
- Removed successful capability-action notices because the updated screen state already provides confirmation; failures still show an inline error.

## 1.18 - 2026-08-27

- Changed User capability Yes/No controls to save through WordPress AJAX without reloading the Capabilities page.
- Added per-control loading states, inline success and error feedback, and live User capability count updates.
- Styled the controls as accessible Yes/No slide switches with a clear enabled state and keyboard focus treatment.
- Removed the Differences/All filter so the complete capability list is always visible.
- Removed the Integration comparison column and explanatory commentary from the Capabilities screen.
- Retained the nonce-protected form submission as a no-JavaScript fallback.

## 1.17 - 2026-08-27

- Made Yes and No values in the User capability column clickable so administrators can remove or restore a capability for User only.
- Preserved explicit User capability exclusions during activation, runtime inheritance, and Sync Admin Rights while keeping new activations enabled by default.
- Added confirmed prefix-group cleanup to remove obsolete capability sets from every stored role on the current site.

## 1.16 - 2026-08-24

- Restored the View details row link when WordPress does not provide one.
- Prevented duplicate View details links by detecting an existing native plugin-details link before adding the fallback.

## 1.15 - 2026-08-24

- Removed the manually generated View details row link so only WordPress's native View details link is displayed.
- Preserved the native plugin-details modal backed by the repository README and changelog.

## 1.14 - 2026-08-24

- Updated the plugin-details Description tab to render the repository README.
- Updated the plugin-details Changelog tab to render the complete repository `CHANGELOG.md`.
- Added cached, sanitised GitHub document rendering with safe fallbacks when GitHub is unavailable.

## 1.13 - 2026-08-24

- Added a native Assigned Users column to the Permission Sets list.
- Removed the Plugin URI header so WordPress no longer displays the Visit plugin site metadata link.
- Retained the GitHub, View details, and nonce-protected Check for updates links required by the GitHub update workflow.

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
