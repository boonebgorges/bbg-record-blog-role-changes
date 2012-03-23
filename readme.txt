=== BBG Record Blog Role Changes ===
Contributors: boonebgorges, slaFFik
Tags: buddypress, tools, capability, blog, multisite
Requires at least: WP 3.2 and BuddyPress 1.5
Tested up to: WP 3.3.1 and BuddyPress 1.5.4
Stable tag: 0.4

Plugin will record all changes in user blog roles (wp_x_capabalities usermeta) across an entire WordPress installation.

== Description ==

Plugin will record all changes in user blog roles (wp_x_capabalities usermeta) across an entire WordPress installation.

Visit [Boone's page](http://teleogistic.net/2012/03/record-user-role-changes-across-a-wordpress-network-for-troubleshooting/) for more information about this plugin.

== Installation ==

1. Upload plugin `/wp-content/plugins/` directory
1. Activate it through the 'Plugins' menu in WordPress
1. That's all.

== Screenshots ==

1. Admin page

== Changelog ==

= 0.4 =
* Move admin page to Network admin area (under Users menu) (if MS is activated, otherwise - under Tools)
* "Delete all records" button in admin area (be careful with it!)
* Some other minor code changes

= 0.3 =
* Added admin page to look the data in dashboard
* Fixed regex for sniffing out blog_ids

= 0.2 =
* Made the code as a WP plugin
* Added BuddyPress abstraction

= 0.1 =
* Initial Boone's code 