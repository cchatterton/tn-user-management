=== TN User Management ===
Contributors: techn
Tags: user management, user roles, capabilities, permissions, multisite
Requires at least: 7.0
Tested up to: 7.1.2
Stable tag: 1.32.2
Requires PHP: 7.4
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

Update discovery and release details are supplied by TN Update Controller. This plugin does not independently request release metadata, repository readmes or changelogs. The explicit controller-install action downloads its official GitHub release ZIP; see Controller installation service below.

GitHub service information:

* Service: https://github.com/
* Terms: https://docs.github.com/en/site-policy/github-terms/github-terms-of-service
* Privacy statement: https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement

A future WordPress.org-distributed edition must use WordPress.org updates and omit the GitHub update checker.

== Changelog ==

= 1.32.2 =
* Lower the PHP requirement to 7.4 to match WordPress 7.0; update the controller installation compatibility check.

= 1.32.1 =
* Replace the independent updater with TN Update Controller integration.
* Standardise author and plugin-row links; preserve feature settings and plugin identity.
* Require WordPress 7.0+ and PHP 8.5+.

= 1.32 =

* Enforced Skip Confirmation Email for both Add Existing User and Add User on multisite subsites.
* Kept both subsite controls visibly checked and disabled to reflect the enforced behaviour.

= 1.31 =

* Disabled account emails to newly created users while retaining administrator notifications.
* Updated Add User screens to show that no account email is sent automatically.

= 1.30 =

* Declared GPL v2-or-later licensing in the plugin package.
* Added a WordPress.org-compatible `readme.txt` with installation, safety, support, compatibility, and external-service information.

== Managed updates ==

Install and activate TN Update Controller to discover and install updates. The plugin row offers Install Techn Update Controller, Activate Techn Update Controller, or Check for updates according to local state and permissions. Feature operation does not require the controller. No release lookup happens while rendering this plugin's row. On multisite the controller must be network active. This plugin release requires WordPress 7.0 and PHP 8.5 or later.

== Controller installation service ==

Only an explicit authorised Install Techn Update Controller action downloads the official controller ZIP from GitHub. No plugin settings or site inventory are submitted; GitHub receives the server IP address and normal request metadata. Routine update discovery is delegated to the installed controller. Repository links open GitHub when selected.
Terms: https://docs.github.com/en/site-policy/github-terms/github-terms-of-service
Privacy: https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement
