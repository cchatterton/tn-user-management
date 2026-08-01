# TN User Management

- Author: Techn
- Version: 1.9
- Status: Production

## Purpose

TN User Management provides email-as-username handling, one-time username migration, role normalisation, permission sets, capability management, and multisite user governance for WordPress.

## Key Features

- Keeps the User role aligned with Administrator capabilities.
- Provides permission sets that control the admin menus visible to User accounts.
- Adds a database-only Integration role with no capabilities and no login access.
- Adds and removes explicitly managed capabilities for Administrator and User.
- Groups capability comparisons by the text before the first underscore.
- Supports native WordPress updates from public GitHub releases.
- Supports WordPress multisite and network activation.

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
- Capabilities created on the Capabilities page are tracked in the `tn731_umg_manual_capabilities` option so only those capabilities can be removed there.
- GitHub updates require a public release tagged with the plugin version and an attached asset named `tn-user-management.zip`.

## Future Considerations

- Add authenticated GitHub API support only if private distribution is explicitly required.
