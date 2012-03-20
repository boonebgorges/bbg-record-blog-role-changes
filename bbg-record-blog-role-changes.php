<?php

/**
 * Record all changes to user blog roles
 *
 * @author Boone B Gorges
 * @license GPLv2
 */

class BBG_RBRC {
	var $table_name;
	var $role_from;
	
	function __construct() {
		global $wpdb;
		
		$this->table_name = $wpdb->base_prefix . 'bbg_rbrc';
		
		// install routine
		add_action( 'admin_init', array( &$this, 'install' ) );
	
		// Before usermeta is changed, get the old role
		add_action( 'update_user_meta', array( &$this, 'catch_role_from' ), 10, 4 );
	
		// record new instances
		add_action( 'updated_user_meta', array( &$this, 'record' ), 10, 4 );
	}
	
	/**
	 * Installation
	 *
	 * To run this, visit the Dashboard as an admin, and add URL param
	 *    ?bbg_action=install_blog_role_recorder
	 */
	function install() {
		global $wpdb;
		
		if ( !is_super_admin() ) {
			return;
		}
		
		if ( empty( $_GET['bbg_action'] ) || 'install_blog_role_recorder' != $_GET['bbg_action'] ) {
			return;
		}
				
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		
		$sql = "CREATE TABLE {$this->table_name} (
			id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			user_id bigint(20) NOT NULL,
			blog_id bigint(20) NOT NULL,
			loggedin_user_id bigint(20) NOT NULL,
			group_id bigint(20) NOT NULL,
			url varchar(75) NOT NULL,
			role_from varchar(75) NOT NULL,
			role_to varchar(75) NOT NULL,
			date datetime NOT NULL )";
		
		dbDelta( $sql );
	}
	
	/**
	 * Get the blog_id out of a wp_x_capabilities meta_key
	 *
	 * Will return 0 if none is found, so this also acts as a test for whether this is a 
	 * cap_key at all
	 */
	function get_blog_id_from_cap_key( $meta_key ) {
		global $wpdb;
		
		$pattern = '/' . $wpdb->base_prefix . '([0-9+?])_capabilities/'; 
		preg_match( $pattern, $meta_key, $matches );
		
		$blog_id = isset( $matches[1] ) ? (int) $matches[1] : 0;
		
		return $blog_id;
	}
	
	/**
	 * This is a dumb trick. Before the new role is saved, look in the user object to get the
	 * old role
	 */
	function catch_role_from( $check, $user_id, $meta_key, $meta_value ) {
		if ( !$blog_id = $this->get_blog_id_from_cap_key( $meta_key ) ) {
			return NULL;
		}
		
		// Get the old role
		$user = new WP_User( $user_id ); 
		$user->for_blog( $blog_id );
		
		if ( !empty( $user->roles[0] ) ) {
			$this->role_from = $user->roles[0];
		}
		
		return NULL;
	}
	
	/**
	 * Record a change
	 */
	function record( $meta_id, $user_id, $meta_key, $role_to ) {
		global $wpdb;
		
		if ( !$blog_id = $this->get_blog_id_from_cap_key( $meta_key ) ) {
			return NULL;
		}
		
		$user_id = (int) $user_id;
		$blog_id = (int) $blog_id;
		$loggedin_user_id = (int) get_current_user_id();
		
		// wp_guess_url() doesn't like wp-admin
		$schema = is_ssl() ? 'https://' : 'http://';
		$url = $schema . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		
		$group_id = (int) bp_get_current_group_id();
		$role_from = $this->role_from;

		// $role_to is in [role] => 1 format
		$role_to = array_pop( array_keys( $role_to ) );

		$date = date( 'Y-m-d h:i:s' );

		$sql = $wpdb->prepare(
			"INSERT INTO {$this->table_name} (
				user_id,
				blog_id,
				loggedin_user_id,
				group_id,
				url,
				role_from,
				role_to,
				date
			) VALUES (
				%d, %d, %d, %d, %s, %s, %s, %s
			)",
				$user_id,
				$blog_id,
				$loggedin_user_id,
				$group_id,
				$url,
				$role_from,
				$role_to,
				$date
		);
		
		$wpdb->query( $sql );
	}

}
new BBG_RBRC;

?>