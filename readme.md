# BBG Record Blog Role Changes

This plugin will record all changes in user blog roles (`wp_x_capabalities` usermeta) across an entire WordPress installation.

The plugin attempts to record BuddyPress-specific information. If you aren't running BuddyPress, be sure to remove those lines.

## Setup

* Put the plugin file into your `wp-content/mu-plugins/` directory.
* Run the installation routine by visiting a Dashboard page as a Super Admin and appending the URL parameter `?bbg_action=install_blog_role_recorder`