# TN User Management

TN User Management provides a deliberately simple user-governance model for WordPress: people either have operational permission or they do not.

- **Administrator** and **User** are permitted roles. Both receive the same WordPress capabilities.
- **Subscriber** is the non-permitted human role. It does not receive the elevated capabilities shared by Administrator and User.
- **Integration** is a non-human, database-only role. It has no effective capabilities and cannot log in.

For permitted users, the plugin controls which parts of the WordPress admin interface they need to see. It does not create progressively weaker versions of the User role by removing capabilities.

## Philosophy: restrict access, not authority

Traditional WordPress role design often creates several capability tiers and tries to predict which actions each tier should be allowed to perform. That approach can become fragile as WordPress and its plugins add new capabilities.

TN User Management takes a different approach:

1. **Decide whether the person is permitted.** An Administrator or User is permitted; a Subscriber is not.
2. **Give permitted people consistent authority.** The User role is kept aligned with the Administrator role, including capabilities introduced by other plugins.
3. **Shape the working interface.** Permission Sets determine which admin menus are visible to each User, so they see the areas relevant to their work.
4. **Use Administrator for governance.** Administrators retain the full admin interface and manage roles, capabilities, Permission Sets, and content-access settings.

This separates two concerns that WordPress commonly combines:

- **Authority** answers: “Is this person trusted to operate the site?”
- **Access** answers: “Which parts of the admin interface should this person use?”

The result is a binary trust model with a tailored workspace, rather than a hierarchy of partially trusted operator roles.

> [!IMPORTANT]
> Permission Sets remove admin menu items; they do not revoke the underlying WordPress capabilities or act as an authorization boundary for direct URLs, custom code, REST requests, or third-party interfaces. A User remains as capable as an Administrator unless the explicit **Own content only** rule applies. Use the Subscriber role when a human must not have operator authority.

## Access model

| Role | Intended use | Effective authority | Admin interface |
| --- | --- | --- | --- |
| Administrator | Site governance and unrestricted administration | Administrator capabilities | Full interface; Permission Sets do not apply |
| User | Trusted day-to-day operator | Kept in sync with Administrator capabilities | Tailored by assigned Permission Sets |
| Subscriber | Human without operational permission | Standard Subscriber capabilities only | No elevated operator authority |
| Integration | Database identity for integrations | No effective capabilities | Authentication is blocked and existing sessions are ended |

On multisite, Super Administrators also bypass Permission Set visibility rules.

## What the plugin delivers

### Administrator-equivalent User role

The plugin creates and maintains a `user` role whose capabilities match the current `administrator` role.

- The role is synchronised during activation and when **Sync Admin Rights** is selected on the Plugins screen.
- Administrator capabilities are also granted to User accounts at runtime. This keeps the two roles aligned when another plugin adds a capability after the last stored-role sync.
- Capabilities added from the plugin's **Capabilities** screen are added to both roles.
- Administrator accounts are not affected by Permission Set menu filtering.

### Permission Sets

Permission Sets are reusable collections of visible WordPress admin areas. They can include:

- native top-level menus;
- public and custom post-type menus;
- individual Tools items;
- individual Settings items; and
- top-level menus registered by third-party plugins.

Permission Sets apply only to accounts with the User role. They may be assigned from either the Permission Set editor or an individual User profile.

When a User has more than one Permission Set, the visible items are combined: an item included by any assigned set is shown. The Dashboard and Profile remain available. If no Permission Set is assigned, the User receives no optional menu items.

The available menu catalogue is captured from the active WordPress installation, so items registered by installed themes and plugins can be included.

### Per-user content scope

An Administrator can assign one of two content modes to an account with the User role:

- **All content** — the default. The User can act on content according to their Administrator-equivalent capabilities.
- **Own content only** — the User can still view public content created by others but cannot edit or delete another author's content.

The ownership rule applies to public post types and is an explicit capability restriction, not just a hidden menu. It does not apply to Administrators or multisite Super Administrators. A missing or invalid setting defaults to **All content**.

### Capability management

The **Permission Sets → Capabilities** screen compares the capabilities held by Administrator, User, Subscriber, and Integration.

- Capabilities are grouped by the text before the first underscore.
- Capabilities created through this screen appear in a dedicated **Custom** group.
- A new capability is added to both Administrator and User.
- Only capabilities created and tracked by this plugin can be removed from this screen.
- The Integration role always has no effective capabilities.

### Email as username

For new users created through supported WordPress admin flows, the plugin sets the username to the lowercase email address.

On activation, it also attempts to migrate existing usernames to lowercase email addresses. A user is skipped if the email is empty, already matches the username, or conflicts with another login. The activation notice reports the number updated and skipped.

Because WordPress multisite stores Super Administrators by login name, the plugin updates that list when a Super Administrator's username is migrated. It also refreshes the activating user's authentication cookie when needed.

### Role normalisation

During activation, the plugin normalises existing users into its access model:

- Super Administrators remain Super Administrators and receive Administrator membership on each site.
- Existing Administrator accounts remain Administrators.
- Existing Subscriber accounts remain Subscribers.
- Existing Integration accounts remain Integration accounts.
- Users with other roles become Users.
- Accounts with no role become Subscribers.

Existing role definitions are not deleted, but users assigned to non-baseline roles may be moved into the User role during activation migration.

### Multisite governance

The plugin supports network activation and applies its baseline roles and migration to every existing site.

- The activating account is protected before role processing starts.
- The activating account is granted Super Administrator access and Administrator membership on every site.
- Existing Super Administrators are added to every site as Administrators.
- Newly created sites receive the baseline roles, the synchronised User role, and the hidden reference account used to discover available menus.
- Accounts without a role are assigned Subscriber on each applicable site.

## Installation

Requirements:

- WordPress 6.0 or newer;
- PHP 8.1 or newer; and
- a database backup before first activation, because activation can change existing usernames and role assignments.

Install from a release:

1. Download `tn-user-management.zip` from the latest GitHub release.
2. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
3. Upload the ZIP and activate it. On multisite, network-activate it if the access model should apply across the network.
4. Review the username-migration notice and confirm the roles of existing accounts.
5. Create Permission Sets and assign them to User accounts.

## Recommended setup

1. Keep site owners and governance staff as **Administrator**.
2. Assign trusted operators to **User**.
3. Create Permission Sets around work areas, such as Content, Commerce, or Marketing.
4. Assign one or more sets to each User. Multiple assignments are additive.
5. Set a User to **Own content only** when they should not modify other authors' public content.
6. Assign people without operator permission to **Subscriber**.
7. Reserve **Integration** for non-human database identities that must never authenticate.

After installing a plugin that introduces new capabilities, use **Sync Admin Rights** from the TN User Management row on the Plugins screen to persist the latest Administrator capabilities to the User role. Runtime inheritance protects active Users in the meantime.

## Updates

TN User Management supports native WordPress updates from public GitHub releases. A release must:

- use a `vX.Y` tag matching the plugin version; and
- include an asset named `tn-user-management.zip`.

The Plugins screen provides **View details**, **GitHub**, and **Check for updates** links. Update metadata is cached to reduce GitHub API requests.

The **View details** modal renders its Description from this README and its Changelog from the repository's `CHANGELOG.md`. Rendered content is cached for six hours and falls back to local explanatory links if GitHub is temporarily unavailable.

## Building a release

Run:

```bash
scripts/build-plugin-zip.sh
```

The script writes `dist/tn-user-management.zip`, with the `tn-user-management/` plugin directory at the top level of the archive.

## Operational considerations

- Activation changes database state. Test on staging and take a backup before deploying to an existing site.
- Username migration may affect systems that store a WordPress login name outside WordPress. Review integration dependencies before activation.
- Permission Sets depend on menus registered by the current theme and plugins. Review them after changing the site's plugin stack.
- Admin-menu hiding is an interface control, not a security control. Any custom endpoint must still perform its own WordPress capability and nonce checks.
- The hidden reference User exists so the plugin can discover menus that are registered conditionally by capability. It is excluded from the normal Users screen.
- GitHub update checks use the public GitHub API and do not support private-repository authentication.

## Project structure

```text
.
├── CHANGELOG.md
├── README.md
├── scripts/
│   └── build-plugin-zip.sh
└── tn-user-management/
    ├── functions/
    ├── scripts/
    ├── styles/
    ├── readme.md
    └── tn-user-management.php
```

See [CHANGELOG.md](CHANGELOG.md) for release history.
