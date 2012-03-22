<?php
// need this to prevent errors if the code is working on a site without BuddyPress
if ( !function_exists('bp_get_current_group_id') ){
    function bp_get_current_group_id(){
        return 0;
    }
}

if ( !function_exists('groups_get_group') ){
    function groups_get_group($array){
        $group = new Stdclass;
        $group->name = '';
        return $group;
    }
}

if ( !function_exists('get_blog_details') ){
    function get_blog_details($blog_id){
        global $wpdb;
        if (is_multisite()){
            $details = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->blogs 
                                                        WHERE blog_id = %d", $blog_id ) );
        }elseif(defined('BP_VERSION')){
            global $bp;
            $details = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value as blogname FROM {$bp->blogs->table_name_blogmeta}
                                                        WHERE blog_id = %d", $blog_id ) );
        }else{
            $details = new Stdclass;
            $details->blogname = get_bloginfo('name');
        }
        return $details;
    }
}

?>