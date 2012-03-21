<?php
/*
Plugin Name: BBG Record Blog Role Changes
Plugin URI: http://teleogistic.net/2012/03/record-user-role-changes-across-a-wordpress-network-for-troubleshooting/
Description: Plugin will record all changes in user blog roles (wp_x_capabalities usermeta) across an entire WordPress installation.
Author: boonebgorges, slaFFik
Version: 0.3
Author URI: http://boone.gorg.es/
License: GPLv2
*/

register_activation_hook( __FILE__, 'rbrc_activation');
function rbrc_activation(){
    $rbrc = array();
    
    // install on activation
    $rbrc['installed'] = BBG_RBRC::install();
    
    add_option('rbrc_options', $rbrc, '', 'yes');
}


class BBG_RBRC {

    var $role_from;
    var $rbrc_options;
    
    function __construct() {
        global $wpdb;
        
        $wpdb->bbg_rbrc_table = $wpdb->base_prefix . 'bbg_rbrc';
        
        $this->rbrc_options = get_option('rbrc_options');
        
        // install routine
        if($this->rbrc_options['installed'] !== true)
            add_action( 'admin_init', array( &$this, 'install' ) );
    
        if (is_admin()){
            add_action( 'admin_init', array( &$this, 'admin_init' ) );
        }
    
        // Before usermeta is changed, get the old role
        add_action( 'update_user_meta', array( &$this, 'catch_role_from' ), 10, 4 );
    
        // record new instances
        add_action( 'updated_user_meta', array( &$this, 'record' ), 10, 4 );
    }
    
    function admin_init(){
        add_management_page(
            __('BBG Record Blog Role Changes', 'rbrc'),
            __('Blog Role Changes', 'rbrc'),
            is_multisite() ? 'manage_network_options' : 'manage_options',
            'rbrc-admin', 
            array(&$this, 'rbrc_admin'));
    }
    
    function rbrc_admin(){
        echo '<h2>' . __( 'Blog Role Changes Data', 'rbrc' ) . '</h2>';
    }
    
    /**
     * Installation
     */
    public function install() {
        global $wpdb;
        
        if ( !is_super_admin() ) {
            return false;
        }
        
        $rbrc_options = get_option('rbrc_options');
        if($rbrc_options['installed'] === true)
            return true;
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        $sql = "CREATE TABLE {$wpdb->bbg_rbrc_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id bigint(20) NOT NULL,
            blog_id bigint(20) NOT NULL,
            loggedin_user_id bigint(20) NOT NULL,
            group_id bigint(20) NOT NULL DEFAULT 0,
            url varchar(75) NOT NULL,
            role_from varchar(75) NOT NULL,
            role_to varchar(75) NOT NULL,
            date datetime NOT NULL )";
        
        dbDelta( $sql );
        
        $rbrc_options['installed'] = true;
        
        update_option('rbrc_options', $rbrc_options, '', 'yes');
        
        return true;
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
        
        if(!is_multisite() && $blog_id == 0)
            $blog_id = 1;

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
        if( !is_array($role_to) )
            return;
            
        $role_to = array_pop( array_keys( $role_to ) );

        $date = date( 'Y-m-d h:i:s' );

        $sql = $wpdb->prepare(
            "INSERT INTO {$wpdb->bbg_rbrc_table} (
                `user_id`,
                `blog_id`,
                `loggedin_user_id`,
                `group_id`,
                `url`,
                `role_from`,
                `role_to`,
                `date`
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

// BP Abstraction
add_action('plugins_loaded', 'rbrc_init', 99);
function rbrc_init(){
    include dirname(__FILE__).'/bbg-bp-abstraction.php';
    new BBG_RBRC;
}

?>