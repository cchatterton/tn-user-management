# TN User Management

- Author: Techn
- Version: 1.21
- Status: Production

## Purpose

TN User Management provides email-as-username handling, one-time username migration, role normalisation, permission sets, capability management, and multisite user governance for WordPress.

## Key Features

- Keeps the User role aligned with Administrator capabilities by default, with explicit per-capability User exclusions.
- Provides permission sets that control the admin menus visible to User accounts.
- Adds a database-only Integration role with no capabilities and no login access.
- Adds and removes explicitly managed capabilities for Administrator and User.
- Lets administrators toggle individual capabilities on or off for the User role without reloading the page.
- Removes obsolete prefix-based capability groups from every stored role on the current site after confirmation.
- Removes any individual capability from every stored role through an AJAX trash action.
- Groups manually added capabilities into a dedicated Custom section.
- Uses underscores to identify grouped capabilities and names each group from the text before the first underscore.
- Supports per-user All content or Own content only editing rules across public post types.
- Supports native WordPress updates from public GitHub releases.
- Supports WordPress multisite and network activation.
- Preserves the activating user's Administrator access and grants Super Admin plus per-site Administrator access on multisite.
- Shows assigned user email addresses directly on the Permission Sets list without requiring an admin-columns plugin.
- Renders the GitHub README and complete repository changelog in the WordPress plugin-details modal.

## Folder Structure

```text
tn-user-management/
├── tn-user-management.php
├── readme.md
├── functions/
├── scripts/
└── styles/
```

## Important Notes

- Requires WordPress 6.0 or newer and PHP 8.1 or newer.
- Integration accounts are intentionally blocked from authentication and have all effective capabilities denied.
- Capabilities created on the Capabilities page are tracked in the `tn731_umg_manual_capabilities` option and are cleaned from that tracking when removed.
- Capabilities intentionally removed from User are stored in `tn731_umg_user_excluded_capabilities` so activation, runtime inheritance, and manual synchronisation preserve the exclusion.
- Per-user content access is stored in `tn731_umg_content_access`; missing or invalid values default to unrestricted All content access.
- Username migration runs once in each activation request, and its activation result notice is consumed after one display.
- GitHub updates require a public release tagged with the plugin version and an attached asset named `tn-user-management.zip`.

## Future Considerations

- Add authenticated GitHub API support only if private distribution is explicitly required.
