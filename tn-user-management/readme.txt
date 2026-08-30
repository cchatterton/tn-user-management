=== TN User Management ===
Contributors: techn
Tags: user management, user roles, capabilities, permissions, multisite
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.32
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage email-based usernames, permission sets, capabilities, roles, and multisite user governance.

== Description ==

TN User Management provides a structured user-governance system for WordPress and WordPress multisite.

Features include:

* Email addresses as usernames for newly created users.
* New-user account emails disabled while the plugin is active.
* Existing and new users added directly to subsites without confirmation or welcome emails.
* One-time migration of existing usernames to email addresses where no conflict exists.
* A User role aligned with Administrator capabilities by default, with explicit per-capability exclusions.
* Permission sets for controlling the admin menus available to User accounts.
* Per-user All content or Own content only editing rules.
* Capability management with AJAX User-role toggles and capability cleanup controls.
* A database-only Integration role that is blocked from authentication.
* Multisite and network-activation support.

Important: activation can update existing usernames, create or synchronise plugin-managed roles, and create an internal reference user. Test the plugin on a staging site and back up the WordPress database before activation.

== Installation ==

1. Back up the WordPress database and test the activation process on a staging site.
2. Upload the `tn-user-management` folder to `/wp-content/plugins/`, or install the plugin ZIP through Plugins > Add New > Upload Plugin.
3. Activate TN User Management. On multisite, choose network activation only when the governance rules should apply across the network.
4. Review Permission Sets and Capabilities before assigning users to the User role.

== Frequently Asked Questions ==

= What happens during activation? =

The plugin ensures its baseline roles exist, synchronises the User role, creates an internal reference user, assigns users without a role to Subscriber, and changes existing usernames to email addresses when the email is valid and does not conflict with another login.

= Can an Integration account log in? =

No. Integration-role authentication is blocked and all effective capabilities are denied.

= Does the plugin support multisite? =

Yes. It supports multisite and network activation. Network activation applies its role and user-governance operations across sites in the network.

== External services ==

The GitHub-distributed edition contacts GitHub to retrieve update metadata, release packages, the repository readme, and changelog content. These requests occur from the WordPress server and do not intentionally include WordPress user data. GitHub receives the normal connection information associated with an HTTP request, such as the server IP address.

GitHub service information:

* Service: https://github.com/
* Terms: https://docs.github.com/en/site-policy/github-terms/github-terms-of-service
* Privacy statement: https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement

A future WordPress.org-distributed edition must use WordPress.org updates and omit the GitHub update checker.

== Changelog ==

= 1.32 =

* Enforced Skip Confirmation Email for both Add Existing User and Add User on multisite subsites.
* Kept both subsite controls visibly checked and disabled to reflect the enforced behaviour.

= 1.31 =

* Disabled account emails to newly created users while retaining administrator notifications.
* Updated Add User screens to show that no account email is sent automatically.

= 1.30 =

* Declared GPL v2-or-later licensing in the plugin package.
* Added a WordPress.org-compatible `readme.txt` with installation, safety, support, compatibility, and external-service information.
