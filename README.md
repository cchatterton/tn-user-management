# TN User Management

TN User Management provides email-as-username handling, one-time username migration, role normalisation, permission sets, capability management, an Integration role, and multisite user governance for WordPress.

## Release

Build the release ZIP with:

```bash
scripts/build-plugin-zip.sh
```

The ZIP is written to `dist/tn-user-management.zip` and contains the `tn-user-management/` plugin folder at the top level.

GitHub releases must use a matching `vX.Y` tag and include the generated `tn-user-management.zip` asset for native WordPress updates.
